<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Foundation\Console\AssetsPublishCommand;
use Flarum\Foundation\Console\CacheClearCommand;
use Flarum\Http\RequestUtil;
use Laminas\Diactoros\CallbackStream;
use Laminas\Diactoros\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Clears the cache, reporting each step as it finishes.
 *
 * The work recompiles every asset bundle for every frontend and locale, which
 * is tens of seconds where the assets live on remote storage. Waiting until the
 * end to answer leaves the admin watching a spinner with no way to tell a slow
 * clear from a dead request, so the steps are streamed as newline-delimited
 * JSON — one object per line, each complete in itself.
 *
 * Only the body streams. Authorisation, CSRF, throttling and error handling all
 * still come from the API middleware: those wrap the response object, and it is
 * the emitter that consumes the body, so a {@see CallbackStream} passes through
 * them untouched and is streamed by {@see \Flarum\Http\Emitter} at the end.
 */
class ClearCacheController implements RequestHandlerInterface
{
    public function __construct(
        protected CacheClearCommand $command,
        protected AssetsPublishCommand $assetsPublishCommand
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        return (new Response())
            ->withHeader('Content-Type', 'application/x-ndjson')
            // Nothing downstream may hold the response back while it accumulates
            // a complete body — which is exactly what a proxy buffering for
            // compression would do.
            ->withHeader('Cache-Control', 'no-cache, no-transform')
            ->withHeader('X-Accel-Buffering', 'no')
            ->withBody(new CallbackStream($this->steps()));
    }

    /**
     * The work itself, run while the response is being written.
     *
     * By this point the headers have been sent, so a failure cannot become an
     * error response: it is written as a step of its own and the stream closes
     * normally, leaving the client to report it. Everything after the caches are
     * emptied is best-effort anyway — the render path rebuilds lazily, as it did
     * before any of this was pre-built.
     */
    private function steps(): callable
    {
        return function (): string {
            $write = function (string $type, array $step): void {
                echo json_encode(['type' => $type] + $step, JSON_UNESCAPED_SLASHES)."\n";

                flush();
            };

            try {
                $report = $this->command->clear(false, $write);

                if ($report === null) {
                    $write('failed', [
                        'step' => 'cache',
                        'message' => 'Could not clear contents of `storage/cache`. Please adjust file permissions and try again.',
                    ]);

                    return '';
                }

                $exitCode = $this->assetsPublishCommand->run(new ArrayInput([]), new NullOutput());

                $exitCode === 0
                    ? $write('cleared', ['name' => 'published assets', 'files' => null])
                    : $write('failed', ['step' => 'assets:publish', 'message' => 'Publishing assets failed.']);
            } catch (\Throwable $e) {
                $write('failed', ['step' => 'clear', 'message' => $e->getMessage()]);
            }

            // The client watches for this rather than inferring completion from
            // the connection closing, which it cannot distinguish from a
            // connection that dropped.
            $write('done', []);

            return '';
        };
    }
}
