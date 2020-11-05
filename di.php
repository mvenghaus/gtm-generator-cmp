<?php

namespace DI;

use Psr\Log\LoggerInterface;

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([

	LoggerInterface::class => function () {

		$logger = new \Monolog\Logger('standard');
		$logger->pushHandler(new \Monolog\Handler\StreamHandler('php://stdout'));

		return $logger;
	},

	\Google_Service_TagManager::class => function () {
		$client = new \Google_Client();
		$client->setApplicationName('gtm generator cmp');
		$client->setAuthConfig(__DIR__ . '/etc/google_client_credentials.json');
		$client->addScope(\Google_Service_TagManager::TAGMANAGER_EDIT_CONTAINERS);

		return new \Google_Service_TagManager($client);
	}

]);

return $containerBuilder->build();
