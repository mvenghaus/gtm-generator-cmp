<?php

namespace Helper;

use Reader\ProjectReader;

class GoogleServiceTagManagerHelper
{
	/** @var ProjectReader */
	private $projectReader;

	/**
	 * @param ProjectReader $projectReader
	 */
	public function __construct(ProjectReader $projectReader)
	{
		$this->projectReader = $projectReader;
	}

	public function getParent()
	{
		return sprintf('accounts/%s/containers/%s/workspaces/%s', $this->projectReader->getAccountId(), $this->projectReader->getContainerId(), $this->projectReader->getWorkspaceId());
	}

}