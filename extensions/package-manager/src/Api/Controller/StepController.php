<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Api\Controller;

use Flarum\ExtensionManager\Run\Drivers;
use Flarum\ExtensionManager\Run\RunStore;
use Flarum\ExtensionManager\Run\StepRunner;
use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Advance the current run by exactly one unit of work.
 *
 * 🚨 This is what the admin page calls every couple of seconds, and it is why an
 * update finishes on a host that cuts every request at thirty seconds. It never
 * loops: one item, then it returns whatever it now knows.
 *
 * It also never lies by omission. A run that is finished says so; a run another
 * driver is holding says that too. Returning an unchanged run with no
 * explanation is the spinner problem, and the whole point of this extension is
 * not to have it.
 */
class StepController implements RequestHandlerInterface
{
    public function __construct(
        private RunStore $runs,
        private StepRunner $runner,
        private Drivers $drivers,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $run = $this->runs->latest();

        if ($run === null) {
            return new JsonResponse(['run' => null, 'idle' => true]);
        }

        if ($run->isFinished()) {
            return new JsonResponse(['run' => $this->runs->present($run), 'idle' => true]);
        }

        $run = $this->runner->step($run->id);

        return new JsonResponse([
            'run' => $this->runs->present($run),
            'idle' => $run->isFinished(),
            // Not an error. Another driver has it; keep polling.
            'busy' => $this->runner->wasBusy(),
            'stale' => $run->isStale(time()),
            'hasWorker' => $this->drivers->hasWorker(),
        ]);
    }
}
