<?php

namespace Handler;

use Google_Service_TagManager;
use Helper\GoogleServiceTagManagerHelper;
use Provider\TriggerProvider;
use Psr\Log\LoggerInterface;
use Reader\ProjectReader;
use Reader\TagReader;
use Renderer\TagRenderer;

class TagHandler
{
	/** @var Google_Service_TagManager */
	private $googleServiceTagManager;
	/** @var ProjectReader */
	private $projectReader;
	/** @var GoogleServiceTagManagerHelper */
	private $googleServiceTagManagerHelper;
	/** @var TagReader */
	private $tagReader;
	/** @var TagRenderer */
	private $tagRenderer;
	/** @var LoggerInterface */
	private $logger;
	/** @var TriggerProvider */
	private $triggerProvider;

	/**
	 * @param Google_Service_TagManager $googleServiceTagManager
	 * @param GoogleServiceTagManagerHelper $googleServiceTagManagerHelper
	 * @param ProjectReader $projectReader
	 * @param TagReader $tagReader
	 * @param TagRenderer $tagRenderer
	 * @param TriggerProvider $triggerProvider
	 * @param LoggerInterface $logger
	 */
	public function __construct(Google_Service_TagManager $googleServiceTagManager,
	                            GoogleServiceTagManagerHelper $googleServiceTagManagerHelper,
	                            ProjectReader $projectReader,
	                            TagReader $tagReader,
	                            TagRenderer $tagRenderer,
	                            TriggerProvider $triggerProvider,
	                            LoggerInterface $logger)
	{
		$this->googleServiceTagManager = $googleServiceTagManager;
		$this->projectReader = $projectReader;
		$this->googleServiceTagManagerHelper = $googleServiceTagManagerHelper;
		$this->tagReader = $tagReader;
		$this->tagRenderer = $tagRenderer;
		$this->logger = $logger;
		$this->triggerProvider = $triggerProvider;
	}

	public function handle()
	{
		$tagList = $this->getTagList();
		foreach ($this->projectReader->getTags() as $tagName => $tagSettings)
		{
			$tagItems = $this->tagReader->loadTagItemsByName($tagName);
			foreach ($tagItems as $tagItem)
			{
				$tagName = $tagItem['name'];

				$firingTriggerId = $this->getFiringTiggerId($tagItem);
				$blockingTriggerId = $this->getBlockingTriggerId($tagItem);

				$tagContent = $this->tagRenderer->render(json_encode($tagItem), $tagSettings);
				$tagHash = md5(sprintf('%s#%s#%s', $tagContent, $firingTriggerId, implode(',', $blockingTriggerId)));
				$tagItem = json_decode($tagContent, JSON_OBJECT_AS_ARRAY);

				$tag = new \Google_Service_TagManager_Tag();
				$tag->setName($tagName);
				$tag->setType($tagItem['type']);
				$tag->setParameter($tagItem['parameter']);
				$tag->setNotes($tagHash);
				$tag->setFiringTriggerId($firingTriggerId);
				$tag->setBlockingTriggerId($blockingTriggerId);

				if (isset($tagList[$tagName]))
				{
					if ($tagList[$tagName]['notes'] != $tagHash)
					{
						$this->logger->debug('tag update', ['name' => $tagName]);

						$this->googleServiceTagManager->accounts_containers_workspaces_tags->update(
							$this->googleServiceTagManagerHelper->getParent() . '/tags/' . $tagList[$tagName]['tagId'],
							$tag
						);
					} else
					{
						$this->logger->debug('tag skip', ['name' => $tagName]);
					}
				} else
				{
					$this->logger->debug('tag create', ['name' => $tagName]);

					$this->googleServiceTagManager->accounts_containers_workspaces_tags->create(
						$this->googleServiceTagManagerHelper->getParent(),
						$tag
					);
				}

				unset($tagList[$tagName]);
			}
		}

		foreach ($tagList as $tagData)
		{
			$this->logger->debug('tag delete', ['name' => $tagData['name']]);

			$this->googleServiceTagManager->accounts_containers_workspaces_tags->delete(
				$this->googleServiceTagManagerHelper->getParent() . '/tags/' . $tagData['tagId']
			);
		}
	}

	private function getTagList()
	{
		$tagList = $this->googleServiceTagManager->accounts_containers_workspaces_tags->listAccountsContainersWorkspacesTags($this->googleServiceTagManagerHelper->getParent());

		$tags = [];
		foreach (($tagList['tag'] ?? []) as $tag)
		{
			$tags[$tag['name']] = $tag;
		}

		return $tags;
	}

	private function getFiringTiggerId($tagItem): int
	{
		$tagFiringTriggerId = $tagItem['firingTriggerId'][0] ?? '';
		if (!empty($tagFiringTriggerId) && mb_strtoupper($tagFiringTriggerId, 'UTF-8') === $tagFiringTriggerId)
		{
			return $this->triggerProvider->getByName($tagFiringTriggerId)['triggerId'];
		}

		return $this->triggerProvider->getByName($tagItem['name'])['triggerId'];
	}

	private function getBlockingTriggerId($tagItem)
	{
		$blockingTriggerIds = $tagItem['blockingTriggerId'] ?? [];
		$list = [];
		foreach ($blockingTriggerIds as $blockingTriggerId)
		{
			$list[] = $this->triggerProvider->getByName($blockingTriggerId)['triggerId'];
		}

		return $list;
	}

}