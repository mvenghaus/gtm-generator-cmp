<?php

namespace Renderer;

class TagRenderer
{

	public function render(string $content, array $settings)
	{
		foreach ($settings as $key => $value)
		{
			$content = str_replace(sprintf('{{%s}}', $key), $value, $content);
		}

		return $content;
	}

}