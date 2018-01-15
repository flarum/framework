<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Tests\Test;

use Mockery;
use PHPUnit\Framework\TestCase as Test;

abstract class TestCase extends Test
{
    use Concerns\CreatesForum;

    public function setUp()
    {
        Mockery::close();

        $this->refreshApplication();

        $this->init();
    }

    protected function init()
    {
        // To be overloaded by children - saves having to do setUp/mockery::close every time
    }
}
