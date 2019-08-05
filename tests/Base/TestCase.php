<?php

declare(strict_types=1);

namespace Tests\Base;

class TestCase extends \PHPUnit\Framework\TestCase {

    /** @var \Nette\DI\Container */
    protected $container;


    public function setUp(): void
    {
        parent::setUp();
        global $container;
        $this->container = $container;
    }

}
