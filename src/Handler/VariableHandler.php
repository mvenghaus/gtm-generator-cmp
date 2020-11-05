<?php

namespace Handler;

use Google_Service_TagManager;
use Helper\GoogleServiceTagManagerHelper;
use Psr\Log\LoggerInterface;
use Reader\ProjectReader;
use Reader\VariableReader;

class VariableHandler
{
	/** @var Google_Service_TagManager */
	private $googleServiceTagManager;
	/** @var ProjectReader */
	private $projectReader;
	/** @var GoogleServiceTagManagerHelper */
	private $googleServiceTagManagerHelper;
	/** @var VariableReader */
	private $variableReader;
	/** @var LoggerInterface */
	private $logger;

	/**
	 * @param Google_Service_TagManager $googleServiceTagManager
	 * @param GoogleServiceTagManagerHelper $googleServiceTagManagerHelper
	 * @param ProjectReader $projectReader
	 * @param VariableReader $variableReader
	 * @param LoggerInterface $logger
	 */
	public function __construct(Google_Service_TagManager $googleServiceTagManager,
	                            GoogleServiceTagManagerHelper $googleServiceTagManagerHelper,
	                            ProjectReader $projectReader,
	                            VariableReader $variableReader,
	                            LoggerInterface $logger)
	{
		$this->googleServiceTagManager = $googleServiceTagManager;
		$this->projectReader = $projectReader;
		$this->googleServiceTagManagerHelper = $googleServiceTagManagerHelper;
		$this->variableReader = $variableReader;
		$this->logger = $logger;
	}

	public function handle()
	{
		$variableList = $this->getVariableList();

		foreach ($this->variableReader->getAll() as $variableData)
		{
			$variableName = $variableData['name'];
			$variableHash = md5(json_encode($variableData));

			$variable = new \Google_Service_TagManager_Variable();
			$variable->setName($variableName);
			$variable->setType($variableData['type']);
			$variable->setParameter($variableData['parameter']);
			$variable->setNotes($variableHash);

			if (isset($variableList[$variableName]))
			{
				if ($variableList[$variableName]['notes'] != $variableHash)
				{
					$this->logger->debug('variable update', ['name' => $variableName]);

					$this->googleServiceTagManager->accounts_containers_workspaces_variables->update(
						$this->googleServiceTagManagerHelper->getParent() . '/variables/' . $variableList[$variableName]['variableId'],
						$variable
					);
				} else
				{
					$this->logger->debug('variable skip', ['name' => $variableName]);
				}
			} else
			{
				$this->logger->debug('variable create', ['name' => $variableName]);

				$this->googleServiceTagManager->accounts_containers_workspaces_variables->create(
					$this->googleServiceTagManagerHelper->getParent(),
					$variable
				);
			}

			unset($variableList[$variableName]);
		}

		foreach ($variableList as $variableData)
		{
			$this->logger->debug('variable delete', ['name' => $variableData['name']]);

			$this->googleServiceTagManager->accounts_containers_workspaces_variables->delete(
				$this->googleServiceTagManagerHelper->getParent() . '/variables/' . $variableData['variableId']
			);
		}
	}

	private function getVariableList()
	{
		$variableList = $this->googleServiceTagManager->accounts_containers_workspaces_variables->listAccountsContainersWorkspacesVariables($this->googleServiceTagManagerHelper->getParent());

		$variables = [];
		foreach (($variableList['variable'] ?? []) as $variable)
		{
			$variables[$variable['name']] = $variable;
		}

		return $variables;
	}

}