<?php

namespace Command;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends Command
{
	/** @var */
	private $container;

	/**
	 * @param ContainerInterface $container
	 */
	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;

		return parent::__construct();
	}

	protected function configure()
	{
		$this
			->setName('generate')
			->setDescription('generate and update via api')
			->addArgument('file', InputArgument::REQUIRED, 'config file');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->container->get(\App::class)->build($input->getArgument('file'));

		return 0;
	}
}