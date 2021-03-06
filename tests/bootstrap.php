<?php
/**
 * This file is a part of the Sakura project <https://linuxclan.com/sakura-php>.
 * Copyright (c) 2015 - 2020 Nikolas Lada <https://nikolaslada.cz>.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$configurator = new \Nette\Configurator;

$configurator->setDebugMode(true);
//$configurator->enableTracy(__DIR__ . '/log');

$configurator->setTimeZone('Europe/Prague');
$configurator->setTempDirectory(__DIR__ . '/tmp');

$configurator
    ->createRobotLoader()
    ->addDirectory(__DIR__ . '/../src/Order')
    ->addDirectory(__DIR__ . '/../src/Recursive')
    ->addDirectory(__DIR__ . '/../src/Traversal')
    ->addDirectory(__DIR__ . '/Base')
    ->register();

$configurator->addConfig(__DIR__ . '/conf/configuration.local.neon');
$configurator->addConfig(__DIR__ . '/conf/configuration.neon');
$configurator->addConfig(__DIR__ . '/conf/services.neon');

$container = $configurator->createContainer();

return $container;
