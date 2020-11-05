<?php

namespace Helper;

class DirectoryHelper
{

	public function getDataDir()
	{
		return __DIR__ . '/../../data/';
	}

	public function getVariableDir()
	{
		return $this->getDataDir() . 'variable/';
	}

	public function getTagDir()
	{
		return $this->getDataDir() . 'tag/';
	}

	public function getTriggerDir()
	{
		return $this->getDataDir() . 'trigger/';
	}

}