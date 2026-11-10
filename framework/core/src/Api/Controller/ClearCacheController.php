<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Closure;
use Flarum\Foundation\Console\AssetsPublishCommand;
use Flarum\Foundation\Console\CacheClearCommand;
use Flarum\Http\EventStream;
use Flarum\Http\RequestUtil;
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
 * clear from a dead request, so the steps are streamed ({@see EventStream}).
 */
class ClearCacheController implements RequestHandlerInterface
{
    public function __construct(
        protected CacheClearCommand $command,
        protected AssetsPublishCommand $assetsPublishCommand,
        protected EventStream $events
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        return $this->events->response($this->steps(...));
    }

    /**
     * The work itself, run while the response is being written.
     *
     * By this point the headers have been sent, so a failure cannot become an
     * error response: it is written as a step of its own and the stream closes
     * normally, leaving the client to report it. Everything after the caches are
     * emptied is best-effort anyway — the render path rebuilds lazily, as it did
     * before any of this was pre-built.
     *
     * @param Closure(string $type, array<string, mixed> $data=): void $write
     */
    private function steps(Closure $write): void
    {
        try {
            $report = $this->command->clear(false, $write);

            if ($report === null) {
                $write('failed', [
                    'step' => 'cache',
                    'message' => 'Could not clear contents of `storage/cache`. Please adjust file permissions and try again.',
                ]);

                return;
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
    }
}
