<?php

namespace Flarum\Testing\integration\Extend;

use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\ConnectionInterface;

class BeginTransactionAndSetDatabase implements ExtenderInterface
{
    /**
     * A callback to set the database connection object on the test case.
     */
    protected $setDbOnTestCase;

    public function __construct(callable $setDbOnTestCase)
    {
        $this->setDbOnTestCase = $setDbOnTestCase;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $db = $container->make(ConnectionInterface::class);

        $db->beginTransaction();

        ($this->setDbOnTestCase)($db);
    }
}
