<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Http;

use Closure;
use Flarum\Foundation\ErrorHandling\Reporter;
use Flarum\Http\EventStream;
use Flarum\Testing\unit\TestCase;
use Illuminate\Container\Container;
use Laminas\Diactoros\CallbackStream;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

class EventStreamTest extends TestCase
{
    /** @var Throwable[] */
    private array $reported = [];

    private function events(): EventStream
    {
        $container = new Container();
        $container->instance('test.reporter', new class($this->reported) implements Reporter {
            public function __construct(private array &$reported)
            {
            }

            public function report(Throwable $error): void
            {
                $this->reported[] = $error;
            }
        });
        $container->tag('test.reporter', Reporter::class);

        return new EventStream($container);
    }

    /**
     * Reading the body runs the callback, as the emitter does when it sends
     * the response, and captures what it wrote.
     *
     * @return list<array<string, mixed>>
     */
    private function written(ResponseInterface $response): array
    {
        ob_start();
        $response->getBody()->getContents();
        $written = (string) ob_get_clean();

        $this->assertStringEndsWith("\n", $written);

        return array_map(fn (string $line) => json_decode($line, true, flags: JSON_THROW_ON_ERROR), explode("\n", trim($written)));
    }

    /**
     * The emitter streams a callback body, and only a callback body.
     */
    #[Test]
    public function the_response_is_streamed_and_not_held_back_downstream()
    {
        $response = $this->events()->response(fn () => null);

        $this->assertInstanceOf(CallbackStream::class, $response->getBody());
        $this->assertStringStartsWith('application/x-ndjson', $response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('no-transform', $response->getHeaderLine('Cache-Control'));
        $this->assertSame('no', $response->getHeaderLine('X-Accel-Buffering'));
    }

    #[Test]
    public function each_event_is_a_line_with_its_type_data_and_time()
    {
        $response = $this->events()->response(function (Closure $send) {
            $send('step', ['name' => 'Ünïcode/path']);
            usleep(20_000);
            $send('done');
        });

        $events = $this->written($response);

        $this->assertSame(['step', 'done'], array_column($events, 'type'));
        $this->assertSame('Ünïcode/path', $events[0]['name']);
        $this->assertIsInt($events[0]['ms']);
        $this->assertGreaterThanOrEqual($events[0]['ms'] + 20, $events[1]['ms']);
    }

    /**
     * By the time the callback runs the status has been sent, so an exception
     * can't become an error response: it's reported, and the client is told.
     */
    #[Test]
    public function an_uncaught_exception_is_reported_and_sent_as_an_error()
    {
        $response = $this->events()->response(function (Closure $send) {
            $send('start');

            throw new RuntimeException('Broken');
        });

        $this->assertSame(['start', 'error'], array_column($this->written($response), 'type'));
        $this->assertCount(1, $this->reported);
        $this->assertSame('Broken', $this->reported[0]->getMessage());
    }

    #[Test]
    public function nothing_runs_until_the_response_is_sent()
    {
        $ran = false;

        $this->events()->response(function () use (&$ran) {
            $ran = true;
        });

        $this->assertFalse($ran);
    }
}
