<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Api\Controller;

use Flarum\ExtensionManager\Exception\RefusedException;
use Flarum\ExtensionManager\Host\StorageDir;
use Flarum\ExtensionManager\Work\UpdateCheck;
use Flarum\Foundation\Paths;
use Flarum\Http\RequestUtil;
use Flarum\Locale\TranslatorInterface;
use GuzzleHttp\ClientInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Check now, rather than waiting for tonight's scheduled run.
 *
 * Cheap enough to be a button: one HTTP call per Flarum extension, no
 * Composer, no resolve. On this forum that is about ten calls, not the 130 it
 * would be without the filter in UpdateCheck::interesting().
 */
class CheckController implements RequestHandlerInterface
{
    /** Seconds "Check now" may spend asking, well inside a 30-second limit. */
    protected const BUDGET = 20;

    public function __construct(
        protected TranslatorInterface $translator,
        protected Paths $paths,
        protected ClientInterface $http,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $check = new UpdateCheck(StorageDir::of($this->paths->storage).'/updates.json', $this->http);

        // The same check as the console command, protected repositories
        // included: see UpdateCheck::refreshSite(). Within a budget, because
        // this is a web request: sixty extensions take about ten seconds when
        // Packagist is well, and a slow Packagist used to run the request
        // into a PHP or gateway timeout and lose every answer.
        $result = $check->refreshSite($this->translator, $this->paths->base, static::BUDGET);

        if ($result === null) {
            throw new RefusedException($this->translator->trans('flarum-extension-manager.api.check.no_lock'), 'missing_lock_file', 500);
        }

        return new JsonResponse([
            'updates' => [
                'available' => $result['updates'],
                'checkedAt' => $result['checkedAt'],
                'stale' => false,
                'uncheckable' => $result['uncheckable'],
                'tracking' => $result['tracking'],
            ],
        ]);
    }
}
