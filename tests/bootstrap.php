<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$configurator = new \Nette\Configurator;

$configurator->setDebugMode(false);
//$configurator->enableTracy(__DIR__ . '/log');

$configurator->setTimeZone('Europe/Prague');
$configurator->setTempDirectory(__DIR__ . '/tmp');

$configurator
    ->createRobotLoader()
    ->addDirectory(__DIR__ . '/../src/Order')    
    ->addDirectory(__DIR__ . '/Base')
    ->register();

$configurator->addConfig(__DIR__ . '/conf/configuration.local.neon');
$configurator->addConfig(__DIR__ . '/conf/configuration.neon');
$configurator->addConfig(__DIR__ . '/conf/services.neon');

$container = $configurator->createContainer();

return $container;
