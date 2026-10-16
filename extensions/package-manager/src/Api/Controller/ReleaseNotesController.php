<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Api\Controller;

use Flarum\ExtensionManager\Host\StorageDir;
use Flarum\ExtensionManager\Lang;
use Flarum\ExtensionManager\Config\AuthTokens;
use Flarum\ExtensionManager\Config\JsonFile;
use Flarum\ExtensionManager\Work\ReleaseNotes;
use Flarum\ExtensionManager\Work\UpdateCheck;
use Flarum\Foundation\Paths;
use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * "Learn more" on a card: the releases between what is installed and what is
 * on offer. Takes a package name only; where to look comes from the saved check.
 */
class ReleaseNotesController implements RequestHandlerInterface
{
    public function __construct(private Paths $paths)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $package = (string) ($request->getQueryParams()['package'] ?? '');
        $updates = (new UpdateCheck(StorageDir::of($this->paths->storage).'/updates.json'))
            ->current($this->paths->base.'/composer.lock')['updates'] ?? [];

        $notes = isset($updates[$package])
            ? (new ReleaseNotes(
                StorageDir::of($this->paths->storage).'/release-notes',
                new AuthTokens(new JsonFile($this->paths->base.'/auth.json')),
            ))->between($updates[$package])
            : null;

        if ($notes === null) {
            return new JsonResponse(['error' => Lang::trans('release_notes.none')], 404);
        }

        return new JsonResponse(['package' => $package, 'from' => $updates[$package]['from'], 'to' => $updates[$package]['to']] + $notes);
    }
}
