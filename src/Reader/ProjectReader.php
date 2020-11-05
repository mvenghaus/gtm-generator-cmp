<?php

namespace Reader;

class ProjectReader
{
	private $data = [];

	public function loadFile($file)
	{
		$this->data = json_decode(file_get_contents($file), JSON_OBJECT_AS_ARRAY);
	}

	public function getAccountId()
	{
		return $this->data['accountId'] ?? '';
	}

	public function getContainerId()
	{
		return $this->data['containerId'] ?? '';
	}

	public function getWorkspaceId()
	{
		return $this->data['workspaceId'] ?? '';
	}

	public function getTags()
	{
		return $this->data['tags'] ?? '';
	}

}