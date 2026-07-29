<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\database;

use Flarum\Extend;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regression test for flarum/framework#4814.
 *
 * Once `db.transactions` is bound (see AfterCommitTest / #4787), listeners
 * implementing ShouldHandleEventsAfterCommit are deferred to the surrounding
 * transaction's commit. The integration harness wraps each test in a
 * transaction that is rolled back and never committed, so without special
 * handling those listeners never run — making after-commit behaviour
 * impossible to test. The testing harness runs these callbacks inline instead.
 */
class AfterCommitListenerTest extends TestCase
{
    #[Test]
    public function after_commit_listener_runs_during_a_test(): void
    {
        AfterCommitListenerSpy::$ran = false;

        $this->extend(
            (new Extend\Event())->listen(AfterCommitListenerTestEvent::class, AfterCommitListenerSpy::class)
        );

        $this->app()->getContainer()->make(Dispatcher::class)->dispatch(new AfterCommitListenerTestEvent());

        $this->assertTrue(
            AfterCommitListenerSpy::$ran,
            'A ShouldHandleEventsAfterCommit listener should run during an integration test.'
        );
    }

    #[Test]
    public function after_commit_event_is_dispatched_during_a_test(): void
    {
        $ran = false;

        $this->extend(
            (new Extend\Event())->listen(AfterCommitDispatchableEvent::class, function () use (&$ran) {
                $ran = true;
            })
        );

        $this->app()->getContainer()->make(Dispatcher::class)->dispatch(new AfterCommitDispatchableEvent());

        $this->assertTrue(
            $ran,
            'A ShouldDispatchAfterCommit event should be dispatched during an integration test.'
        );
    }
}

class AfterCommitListenerTestEvent
{
}

class AfterCommitDispatchableEvent implements ShouldDispatchAfterCommit
{
}

class AfterCommitListenerSpy implements ShouldHandleEventsAfterCommit
{
    public static bool $ran = false;

    public function handle(AfterCommitListenerTestEvent $event): void
    {
        static::$ran = true;
    }
}
