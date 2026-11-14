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
    public function insert_before_a_missing_anchor_is_skipped_with_a_warning()
    {
        $this->assertEquals(['a', 'b'], $this->stack(
            ['a', 'b'],
            (new Extend\Middleware('forum'))->insertBefore('gone', 'new')
        ));

        $this->assertCount(1, $this->warnings);
        $this->assertStringContainsString('Skipped inserting middleware [new] relative to [gone]', $this->warnings[0]);
    }

    #[Test]
    public function insert_after_a_missing_anchor_is_skipped_with_a_warning()
    {
        $this->assertEquals(['a', 'b'], $this->stack(
            ['a', 'b'],
            (new Extend\Middleware('forum'))->insertAfter('gone', 'new')
        ));

        $this->assertCount(1, $this->warnings);
        $this->assertStringContainsString('[forum] middleware stack', $this->warnings[0]);
    }

    #[Test]
    public function inserting_relative_to_an_anchor_another_extender_replaced_is_skipped()
    {
        // The realistic case: two extensions touching the same region of the stack,
        // applied in extension order.
        $container = $this->container(['a', 'b']);

        (new Extend\Middleware('forum'))->replace('b', 'replacement')->extend($container);
        (new Extend\Middleware('forum'))->insertAfter('b', 'new')->extend($container);

        $this->assertEquals(['a', 'replacement'], array_values($container->make('flarum.forum.middleware')));
        $this->assertCount(1, $this->warnings);
    }

    #[Test]
    public function anchors_are_compared_strictly()
    {
        // Loosely, the numeric strings '1e1' and '10' are equal.
        $this->assertEquals(['10', 'b'], $this->stack(
            ['10', 'b'],
            (new Extend\Middleware('forum'))->insertAfter('1e1', 'new')
        ));

        $this->assertCount(1, $this->warnings);
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
