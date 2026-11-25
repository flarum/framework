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
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class MiddlewareTest extends TestCase
{
    protected array $warnings = [];

    protected function container(array $middleware): Container
    {
        $this->warnings = [];

        $container = new Container();
        $container->instance('flarum.forum.middleware', $middleware);
        $container->instance(LoggerInterface::class, new class($this->warnings) extends AbstractLogger {
            public function __construct(private array &$warnings)
            {
            }

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->warnings[] = (string) $message;
            }
        });

        return $container;
    }

    protected function stack(array $middleware, Extend\Middleware $extender): array
    {
        $container = $this->container($middleware);

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
    public function inserting_relative_to_a_missing_anchor_fails()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cannot insert middleware [new] relative to [gone]');

        $this->stack(['a', 'b'], (new Extend\Middleware('forum'))->insertBefore('gone', 'new'));
    }

    #[Test]
    public function insert_before_an_optional_missing_anchor_is_skipped_with_a_warning()
    {
        $this->assertEquals(['a', 'b'], $this->stack(
            ['a', 'b'],
            (new Extend\Middleware('forum'))->insertBefore('gone', 'new', optional: true)
        ));

        $this->assertCount(1, $this->warnings);
        $this->assertStringContainsString('[new] relative to [gone]', $this->warnings[0]);
    }

    #[Test]
    public function insert_after_an_optional_missing_anchor_is_skipped_with_a_warning()
    {
        $this->assertEquals(['a', 'b'], $this->stack(
            ['a', 'b'],
            (new Extend\Middleware('forum'))->insertAfter('gone', 'new', optional: true)
        ));

        $this->assertCount(1, $this->warnings);
        $this->assertStringContainsString('[forum] middleware stack', $this->warnings[0]);
    }

    #[Test]
    public function inserting_relative_to_an_anchor_another_extender_replaced_uses_the_replacement()
    {
        // Two extensions touching the same region of the stack, applied in extension order.
        $container = $this->container(['a', 'b', 'c']);

        (new Extend\Middleware('forum'))->replace('b', 'replacement')->extend($container);
        (new Extend\Middleware('forum'))->insertBefore('b', 'before')->insertAfter('b', 'after')->extend($container);

        $this->assertEquals(['a', 'before', 'replacement', 'after', 'c'], array_values($container->make('flarum.forum.middleware')));
        $this->assertEmpty($this->warnings);
    }

    #[Test]
    public function replacements_are_followed_through_a_chain()
    {
        $container = $this->container(['a', 'b']);

        (new Extend\Middleware('forum'))->replace('b', 'b2')->extend($container);
        (new Extend\Middleware('forum'))->replace('b2', 'b3')->extend($container);
        (new Extend\Middleware('forum'))->insertAfter('b', 'new')->extend($container);

        $this->assertEquals(['a', 'b3', 'new'], array_values($container->make('flarum.forum.middleware')));
    }

    #[Test]
    public function an_anchor_replaced_and_then_removed_is_missing()
    {
        $container = $this->container(['a', 'b']);

        (new Extend\Middleware('forum'))->replace('b', 'b2')->extend($container);
        (new Extend\Middleware('forum'))->remove('b2')->extend($container);
        (new Extend\Middleware('forum'))->insertAfter('b', 'new', optional: true)->extend($container);

        $this->assertEquals(['a'], array_values($container->make('flarum.forum.middleware')));
        $this->assertCount(1, $this->warnings);
    }

    #[Test]
    public function anchors_are_compared_strictly()
    {
        // Loosely, the numeric strings '1e1' and '10' are equal.
        $this->assertEquals(['10', 'b'], $this->stack(
            ['10', 'b'],
            (new Extend\Middleware('forum'))->insertAfter('1e1', 'new', optional: true)
        ));

        $this->assertCount(1, $this->warnings);
    }

    #[Test]
    public function inserting_after_another_extender_removed_a_middleware_uses_the_anchor_position()
    {
        // Removal leaves a gap in the keys, which must not be mistaken for an offset.
        $container = $this->container(['a', 'b', 'c']);

        (new Extend\Middleware('forum'))->remove('a')->extend($container);
        (new Extend\Middleware('forum'))->insertBefore('b', 'new')->extend($container);

        $this->assertEquals(['new', 'b', 'c'], array_values($container->make('flarum.forum.middleware')));
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
