<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Api\Controller;

use Flarum\ExtensionManager\Config\AuthTokens;
use Flarum\ExtensionManager\Config\JsonFile;
use Flarum\ExtensionManager\Exception\RefusedException;
use Flarum\ExtensionManager\Host\StorageDir;
use Flarum\ExtensionManager\Work\ReleaseNotes;
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
 * "Learn more" on a card: the releases between what is installed and what is
 * on offer. Takes a package name only; where to look comes from the saved check.
 */
class ReleaseNotesController implements RequestHandlerInterface
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected Paths $paths,
        protected ClientInterface $http,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $package = (string) ($request->getQueryParams()['package'] ?? '');
        $updates = (new UpdateCheck(StorageDir::of($this->paths->storage).'/updates.json', $this->http))
            ->current($this->paths->base.'/composer.lock')['updates'] ?? [];

        $notes = isset($updates[$package])
            ? (new ReleaseNotes(
                StorageDir::of($this->paths->storage).'/release-notes',
                new AuthTokens($this->translator, new JsonFile($this->translator, $this->paths->base.'/auth.json')),
                $this->http,
            ))->between($updates[$package])
            : null;

        if ($notes === null) {
            throw new RefusedException($this->translator->trans('flarum-extension-manager.api.release_notes.none'), 'no_release_notes', 404);
        }

        return new JsonResponse(['package' => $package, 'from' => $updates[$package]['from'], 'to' => $updates[$package]['to']] + $notes);
    }
}
