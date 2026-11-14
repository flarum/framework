<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http;

use Closure;
use Flarum\Foundation\ErrorHandling\Reporter;
use Illuminate\Contracts\Container\Container;
use Laminas\Diactoros\CallbackStream;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * A response that reaches the browser as it's written, for work that takes a
 * while and has something to show along the way: a step finished, a line of
 * output, or a reply being written.
 *
 * The body is newline-delimited JSON, one event per line. Each event has its
 * `type`, whatever the callback gives with it, and `ms`, the milliseconds
 * since the stream began. In the browser, `flarum/common/utils/readEventStream`,
 * a chunk loaded when it's first imported, hands over each event as its line
 * arrives.
 *
 *     public function handle(ServerRequestInterface $request): ResponseInterface
 *     {
 *         RequestUtil::getActor($request)->assertAdmin();
 *
 *         return $this->events->response(function (Closure $send) {
 *             foreach ($this->importer->batches() as $batch) {
 *                 $send('imported', ['count' => $this->importer->import($batch)]);
 *             }
 *
 *             $send('done');
 *         });
 *     }
 *
 * The callback runs once the middleware has passed the request, as the
 * response is sent ({@see Emitter}). By then the status has been sent too, so
 * a failure can't be an error response: send it as an event of its own, and
 * end with an event saying the work is done, as a dropped connection looks
 * just like a finished one. An exception the callback doesn't catch is
 * reported like any other, and sent as `{"type": "error"}`.
 *
 * Proxies are asked not to hold the response back, and PHP doesn't, but a
 * server can still be set up to: the admin dashboard warns when it is.
 */
class EventStream
{
    public function __construct(
        protected Container $container
    ) {
    }

    /**
     * @param Closure(Closure(string $type, array<string, mixed> $data=): void $send): void $callback
     */
    public function response(Closure $callback): ResponseInterface
    {
        return (new Response())
            ->withHeader('Content-Type', 'application/x-ndjson; charset=utf-8')
            // Nothing downstream may hold the response back to compress or cache it.
            ->withHeader('Cache-Control', 'no-cache, no-transform')
            // nginx, and the proxies that follow it.
            ->withHeader('X-Accel-Buffering', 'no')
            ->withBody(new CallbackStream(function () use ($callback): string {
                $this->run($callback);

                return '';
            }));
    }

    /**
     * @param Closure(Closure(string $type, array<string, mixed> $data=): void $send): void $callback
     */
    protected function run(Closure $callback): void
    {
        $start = hrtime(true);

        $send = function (string $type, array $data = []) use ($start): void {
            echo json_encode(
                ['type' => $type] + $data + ['ms' => intdiv(hrtime(true) - $start, 1_000_000)],
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
            )."\n";

            flush();
        };

        try {
            $callback($send);
        } catch (Throwable $error) {
            foreach ($this->container->tagged(Reporter::class) as $reporter) {
                $reporter->report($error);
            }

            $send('error');
        }
    }
}
