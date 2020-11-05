<?php

namespace Reader;

use Helper\DirectoryHelper;
use Renderer\TagRenderer;

class VariableReader
{
	/** @var ProjectReader */
	private $projectReader;
	/** @var DirectoryHelper */
	private $directoryHelper;
	/** @var TagRenderer */
	private $tagRenderer;

	/**
	 * @param ProjectReader $projectReader
	 * @param DirectoryHelper $directoryHelper
	 * @param TagRenderer $tagRenderer
	 */
	public function __construct(ProjectReader $projectReader,
	                            DirectoryHelper $directoryHelper,
	                            TagRenderer $tagRenderer)
	{
		$this->projectReader = $projectReader;
		$this->directoryHelper = $directoryHelper;
		$this->tagRenderer = $tagRenderer;
	}

	public function getAll()
	{
		$list = [];
		$filePattern = $this->directoryHelper->getVariableDir() . '*.json';
		foreach (glob($filePattern) as $file)
		{
			$list[] = json_decode(file_get_contents($file), JSON_OBJECT_AS_ARRAY);
		}

		foreach ($this->projectReader->getTags() as $tagName => $tagSettings)
		{
			$tagVariableDir = sprintf('%s%s/variable/', $this->directoryHelper->getTagDir(), $tagName);
			if (is_dir($tagVariableDir))
			{
				$tagVariableFilePattern = $tagVariableDir . '*.json';
				foreach (glob($tagVariableFilePattern) as $file)
				{
					$list[] = json_decode($this->tagRenderer->render(file_get_contents($file), $tagSettings), JSON_OBJECT_AS_ARRAY);
				}
			}
		}

		return $list;
	}

}