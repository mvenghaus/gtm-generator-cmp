<?php

namespace Handler;

use Google_Service_TagManager;
use Helper\GoogleServiceTagManagerHelper;
use Psr\Log\LoggerInterface;
use Reader\ProjectReader;
use Reader\TagReader;
use Reader\TriggerReader;

class TriggerHandler
{
	/** @var Google_Service_TagManager */
	private $googleServiceTagManager;
	/** @var GoogleServiceTagManagerHelper */
	private $googleServiceTagManagerHelper;
	/** @var ProjectReader */
	private $projectReader;
	/** @var TagReader */
	private $tagReader;
	/** @var TriggerReader */
	private $triggerReader;
	/** @var LoggerInterface */
	private $logger;
	/** @var array */
	private $triggerList;

	/**
	 * @param Google_Service_TagManager $googleServiceTagManager
	 * @param GoogleServiceTagManagerHelper $googleServiceTagManagerHelper
	 * @param ProjectReader $projectReader
	 * @param TagReader $tagReader
	 * @param TriggerReader $triggerReader
	 * @param LoggerInterface $logger
	 */
	public function __construct(Google_Service_TagManager $googleServiceTagManager,
	                            GoogleServiceTagManagerHelper $googleServiceTagManagerHelper,
	                            ProjectReader $projectReader,
	                            TagReader $tagReader,
	                            TriggerReader $triggerReader,
	                            LoggerInterface $logger)
	{

		$this->googleServiceTagManager = $googleServiceTagManager;
		$this->googleServiceTagManagerHelper = $googleServiceTagManagerHelper;
		$this->projectReader = $projectReader;
		$this->tagReader = $tagReader;
		$this->triggerReader = $triggerReader;
		$this->logger = $logger;
	}

	public function handle()
	{
		$this->triggerList = $this->getTriggerList();

		$this->handleTagTrigger();
		$this->handleFixedTrigger();
		$this->deleteOldTrigger();
	}

	private function handleTagTrigger()
	{
		foreach ($this->projectReader->getTags() as $tagName => $tagSettings)
		{
			if (!isset($tagSettings['cmpVendor']))
			{
				$this->logger->debug('ignoring tag trigger - no cmpVendor defined', ['name' => $tagName]);
				continue;
			}

			$tagItems = $this->tagReader->loadTagItemsByName($tagName);
			foreach ($tagItems as $tagItem)
			{
				$firingTriggerId = $tagItem['firingTriggerId'][0];
				if (mb_strtoupper($firingTriggerId, 'UTF-8') === $firingTriggerId)
				{
					continue;
				}

				$params = $this->parseParams($firingTriggerId ?? '');

				$params[] = ['type' => 'equals', 'name' => '{{cmpGDPR}}', 'value' => '1'];
				$params[] = ['type' => 'contains', 'name' => '{{cmpCustomVendorsConsent}}', 'value' => sprintf(',%s,', $tagSettings['cmpVendor'])];

				$triggerName = $tagItem['name'];
				$triggerHash = md5(sprintf('%s#%s', $triggerName, json_encode($params)));

				$trigger = new \Google_Service_TagManager_Trigger();
				$trigger->setName($triggerName);
				$trigger->setType('custom_event');
				$trigger->setNotes($triggerHash);
				$trigger->setCustomEventFilter($this->createCustomEventFilter());
				$trigger->setFilter($this->createFilterFromParams($params));

				$this->saveTrigger($trigger);
			}
		}
	}

	private function handleFixedTrigger()
	{
		foreach ($this->triggerReader->getAll() as $triggerData)
		{
			$triggerName = $triggerData['name'];
			$triggerHash = md5(sprintf('%s#%s', $triggerName, json_encode($triggerData['customEventFilter'] ?? [])));

			$trigger = new \Google_Service_TagManager_Trigger();
			$trigger->setName($triggerName);
			$trigger->setType($triggerData['type']);
			$trigger->setNotes($triggerHash);
			if (isset($triggerData['customEventFilter']))
			{
				$trigger->setCustomEventFilter($triggerData['customEventFilter']);
			}

			$this->saveTrigger($trigger);
		}
	}

	private function deleteOldTrigger()
	{
		foreach ($this->triggerList as $triggerData)
		{
			$this->logger->debug('trigger delete', ['name' => $triggerData['name']]);

			$this->googleServiceTagManager->accounts_containers_workspaces_triggers->delete(
				$this->googleServiceTagManagerHelper->getParent() . '/triggers/' . $triggerData['triggerId']
			);
		}
	}

	private function saveTrigger(\Google_Service_TagManager_Trigger $trigger)
	{
		$triggerName = $trigger->getName();
		$triggerHash = $trigger->getNotes();

		if (isset($this->triggerList[$triggerName]))
		{
			if ($this->triggerList[$triggerName]['notes'] != $triggerHash)
			{
				$this->logger->debug('trigger update', ['name' => $triggerName]);

				$this->googleServiceTagManager->accounts_containers_workspaces_triggers->update(
					$this->googleServiceTagManagerHelper->getParent() . '/triggers/' . $this->triggerList[$triggerName]['triggerId'],
					$trigger
				);
			} else
			{
				$this->logger->debug('trigger skip', ['name' => $triggerName]);
			}
		} else
		{
			$this->logger->debug('trigger create', ['name' => $triggerName]);

			$this->googleServiceTagManager->accounts_containers_workspaces_triggers->create(
				$this->googleServiceTagManagerHelper->getParent(),
				$trigger
			);
		}

		unset($this->triggerList[$triggerName]);
	}

	private function getTriggerList()
	{
		$triggerList = $this->googleServiceTagManager->accounts_containers_workspaces_triggers->listAccountsContainersWorkspacesTriggers($this->googleServiceTagManagerHelper->getParent());

		$triggers = [];
		foreach (($triggerList['trigger'] ?? []) as $trigger)
		{
			$triggers[$trigger['name']] = $trigger;
		}

		return $triggers;
	}

	private function createFilterFromParams(array $params): array
	{
		$filter = [];
		foreach ($params as $paramData)
		{
			$condition = new \Google_Service_TagManager_Condition();
			$condition->setType($paramData['type']);
			$condition->setParameter([
				['type' => 'template', 'key' => 'arg0', 'value' => $paramData['name']],
				['type' => 'template', 'key' => 'arg1', 'value' => $paramData['value']]
			]);

			$filter[] = $condition;
		}

		return $filter;
	}

	private function createCustomEventFilter(): array
	{
		$condition = new \Google_Service_TagManager_Condition();
		$condition->setType('equals');
		$condition->setParameter([
			['type' => 'template', 'key' => 'arg0', 'value' => '{{_event}}'],
			['type' => 'template', 'key' => 'arg1', 'value' => 'cmpEvent']
		]);

		return [$condition];
	}

	private function parseParams(string $triggerQuery): array
	{
		preg_match_all("/([^=]+)='([^']+)'/is", $triggerQuery, $results);

		$params = [];
		foreach ($results[1] as $key => $name)
		{
			$name = trim($name);
			$value = $results[2][$key] ?? '';
			if ($name == '{{Page Type}}' && $value == 'ALL PAGES')
			{
				continue;
			}

			$params[$name] = [
				'type' => 'equals',
				'name' => $name,
				'value' => $value,
			];
		}

		return $params;
	}

}