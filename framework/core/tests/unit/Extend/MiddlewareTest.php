<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Extend;

use Flarum\Extend;
use Flarum\Testing\unit\TestCase;
use Illuminate\Container\Container;
use PHPUnit\Framework\Attributes\Test;

class MiddlewareTest extends TestCase
{
    protected function stack(array $middleware, Extend\Middleware $extender): array
    {
        $container = new Container();
        $container->instance('flarum.forum.middleware', $middleware);

        $extender->extend($container);

        return array_values($container->make('flarum.forum.middleware'));
    }

    #[Test]
    public function insert_before_places_the_middleware_ahead_of_its_anchor()
    {
        $this->assertEquals(['a', 'new', 'b'], $this->stack(
            ['a', 'b'],
            (new Extend\Middleware('forum'))->insertBefore('b', 'new')
        ));
    }

    #[Test]
    public function insert_after_places_the_middleware_behind_its_anchor()
    {
        $this->assertEquals(['a', 'new', 'b'], $this->stack(
            ['a', 'b'],
            (new Extend\Middleware('forum'))->insertAfter('a', 'new')
        ));
    }

    #[Test]
    public function insert_before_a_missing_anchor_throws()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot insert middleware [new] relative to [gone]');

        $this->stack(['a', 'b'], (new Extend\Middleware('forum'))->insertBefore('gone', 'new'));
    }

    #[Test]
    public function insert_after_a_missing_anchor_throws()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('[forum] middleware stack');

        $this->stack(['a', 'b'], (new Extend\Middleware('forum'))->insertAfter('gone', 'new'));
    }

    #[Test]
    public function inserting_relative_to_an_anchor_another_extender_replaced_throws()
    {
        // The realistic case: two extensions touching the same region of the stack,
        // applied in extension order.
        $this->expectException(\InvalidArgumentException::class);

        $container = new Container();
        $container->instance('flarum.forum.middleware', ['a', 'b']);

        (new Extend\Middleware('forum'))->replace('b', 'replacement')->extend($container);
        (new Extend\Middleware('forum'))->insertAfter('b', 'new')->extend($container);

        $container->make('flarum.forum.middleware');
    }

    #[Test]
    public function removing_a_missing_middleware_is_still_a_no_op()
    {
        $this->assertEquals(['a', 'b'], $this->stack(
            ['a', 'b'],
            (new Extend\Middleware('forum'))->remove('gone')
        ));
    }
}
