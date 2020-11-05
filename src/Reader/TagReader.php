<?php

namespace Reader;

use Helper\DirectoryHelper;

class TagReader
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

	public function loadTagItemsByName($name)
	{
		$file = sprintf('%s%s/%s.json', $this->directoryHelper->getTagDir(), $name, $name);

		return json_decode(file_get_contents($file), JSON_OBJECT_AS_ARRAY);
	}

}