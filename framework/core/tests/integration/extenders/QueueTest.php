<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Testing\integration\TestCase;
use Illuminate\Queue\QueueRoutes;
use PHPUnit\Framework\Attributes\Test;

class QueueTest extends TestCase
{
    private function routes(): QueueRoutes
    {
        return $this->app()->getContainer()->make('queue.routes');
    }

    #[Test]
    public function no_routes_are_registered_by_default(): void
    {
        $this->app();

        $this->assertSame([], $this->routes()->all());
    }

    #[Test]
    public function route_registers_a_job_class(): void
    {
        $this->extend(
            (new Extend\Queue())->route(QueueRoutedStub::class, 'priority')
        );

        $this->app();

        $this->assertSame('priority', $this->routes()->getQueue(new QueueRoutedStub()));
    }

    #[Test]
    public function multiple_extenders_compose(): void
    {
        $this->extend((new Extend\Queue())->route(QueueRoutedStub::class, 'a'));
        $this->extend((new Extend\Queue())->route(QueueRoutedStubB::class, 'b'));

        $this->app();

        $this->assertSame('a', $this->routes()->getQueue(new QueueRoutedStub()));
        $this->assertSame('b', $this->routes()->getQueue(new QueueRoutedStubB()));
    }
}

class QueueRoutedStub
{
}

class QueueRoutedStubB
{
}
