<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Extend;

use Flarum\Extend\Event;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Every form of listener documented on {@see Event::listen()} has to be
 * accepted, whatever PHP makes of it: a class name with an instance method is
 * not `callable` to PHP, but the dispatcher resolves the class through the
 * container and calls the method on the instance.
 */
class EventTest extends TestCase
{
    public static function listeners(): array
    {
        return [
            'a closure' => [fn () => null],
            'the class of a listener with a handle method' => [ListenerWithHandle::class],
            'a class name and a static method' => [[ListenerWithMethods::class, 'staticMethod']],
            'a class name and an instance method' => [[ListenerWithMethods::class, 'instanceMethod']],
            'an object and an instance method' => [[new ListenerWithMethods(), 'instanceMethod']],
        ];
    }

    #[Test]
    #[DataProvider('listeners')]
    public function a_documented_listener_is_accepted(mixed $listener)
    {
        $extender = new Event();

        $this->assertSame($extender, $extender->listen('some.event', $listener));
    }
}

class ListenerWithHandle
{
    public function handle(): void
    {
    }
}

class ListenerWithMethods
{
    public static function staticMethod(): void
    {
    }

    public function instanceMethod(): void
    {
    }
}
