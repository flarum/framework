<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\integration;

use PHPUnit\Framework\TestCase as Test;

abstract class TestCase extends Test
{
    use CreatesForum,
        MakesApiRequests;

    public function setUp()
    {
        $this->refreshApplication();
        $this->init();
    }

    protected function init()
    {
        // .. allows implementation by children without the need to call the parent.
    }
}
