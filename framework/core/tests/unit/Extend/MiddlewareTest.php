<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Extend;

use Flarum\Extend\Middleware;
use Flarum\Testing\unit\TestCase;
use Illuminate\Container\Container;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Stringable;

class MiddlewareTest extends TestCase
{
    private Container $container;
    private RecordingLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
        $this->container->instance(LoggerInterface::class, $this->logger = new RecordingLogger());
        $this->container->singleton('flarum.forum.middleware', fn () => ['First', 'Second', 'Third']);
    }

    private function stack(Middleware $extender): array
    {
        $extender->extend($this->container);

        return array_values($this->container->make('flarum.forum.middleware'));
    }

    #[Test]
    public function insert_before_a_missing_middleware_inserts_nothing()
    {
        $stack = $this->stack((new Middleware('forum'))->insertBefore('Missing', 'New'));

        $this->assertSame(['First', 'Second', 'Third'], $stack);
        $this->assertCount(1, $this->logger->warnings);
        $this->assertStringContainsString('Missing', $this->logger->warnings[0]);
    }

    #[Test]
    public function insert_after_a_missing_middleware_inserts_nothing()
    {
        $stack = $this->stack((new Middleware('forum'))->insertAfter('Missing', 'New'));

        $this->assertSame(['First', 'Second', 'Third'], $stack);
        $this->assertCount(1, $this->logger->warnings);
    }

    #[Test]
    public function middlewares_inserted_before_the_same_middleware_keep_their_order()
    {
        $stack = $this->stack(
            (new Middleware('forum'))
                ->insertBefore('Second', 'NewA')
                ->insertBefore('Second', 'NewB')
        );

        $this->assertSame(['First', 'NewA', 'NewB', 'Second', 'Third'], $stack);
    }

    #[Test]
    public function middlewares_inserted_after_the_same_middleware_keep_their_order()
    {
        $stack = $this->stack(
            (new Middleware('forum'))
                ->insertAfter('Second', 'NewA')
                ->insertAfter('Second', 'NewB')
        );

        $this->assertSame(['First', 'Second', 'NewA', 'NewB', 'Third'], $stack);
        $this->assertSame([], $this->logger->warnings);
    }
}

class RecordingLogger extends AbstractLogger
{
    /** @var string[] */
    public array $warnings = [];

    public function log($level, string|Stringable $message, array $context = []): void
    {
        if ($level === 'warning') {
            $this->warnings[] = (string) $message;
        }
    }
}
