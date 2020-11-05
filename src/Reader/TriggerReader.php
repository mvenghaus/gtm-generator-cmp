<?php

namespace Reader;

use Helper\DirectoryHelper;

class TriggerReader
{
	/** @var ProjectReader */
	private $projectReader;
	/** @var DirectoryHelper */
	private $directoryHelper;

	/**
	 * @param ProjectReader $projectReader
	 * @param DirectoryHelper $directoryHelper
	 */
	public function __construct(ProjectReader $projectReader,
	                            DirectoryHelper $directoryHelper)
	{
		$this->projectReader = $projectReader;
		$this->directoryHelper = $directoryHelper;
	}

	public function getAll()
	{
		$list = [];
		$filePattern = $this->directoryHelper->getTriggerDir() . '*.json';
		foreach (glob($filePattern) as $file)
		{
			$list[] = json_decode(file_get_contents($file), JSON_OBJECT_AS_ARRAY);
		}


		return $list;
	}

}