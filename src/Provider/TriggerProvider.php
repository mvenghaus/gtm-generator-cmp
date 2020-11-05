<?php

namespace Provider;

use Google_Service_TagManager;
use Helper\GoogleServiceTagManagerHelper;

class TriggerProvider
{
	/** @var Google_Service_TagManager */
	private $googleServiceTagManager;
	/** @var GoogleServiceTagManagerHelper */
	private $googleServiceTagManagerHelper;
	/** @var array */
	private $triggers;

	/**
	 * @param Google_Service_TagManager $googleServiceTagManager
	 * @param GoogleServiceTagManagerHelper $googleServiceTagManagerHelper
	 */
	public function __construct(Google_Service_TagManager $googleServiceTagManager,
	                            GoogleServiceTagManagerHelper $googleServiceTagManagerHelper)
	{
		$this->googleServiceTagManager = $googleServiceTagManager;
		$this->googleServiceTagManagerHelper = $googleServiceTagManagerHelper;
	}

	public function getByName(string $name)
	{
		$this->loadTriggers();

		return $this->triggers[$name] ?? null;
	}

	private function loadTriggers(): void
	{
		if (is_null($this->triggers))
		{
			$triggerList = $this->googleServiceTagManager->accounts_containers_workspaces_triggers->listAccountsContainersWorkspacesTriggers($this->googleServiceTagManagerHelper->getParent());

			$this->triggers = [];
			foreach (($triggerList['trigger'] ?? []) as $trigger)
			{
				$this->triggers[$trigger['name']] = $trigger;
			}
		}
	}

}