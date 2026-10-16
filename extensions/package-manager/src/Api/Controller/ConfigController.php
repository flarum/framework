<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Api\Controller;

use Flarum\ExtensionManager\Lang;
use Flarum\ExtensionManager\Config\AuthTokens;
use Flarum\ExtensionManager\Config\JsonFile;
use Flarum\ExtensionManager\Config\Repositories;
use Flarum\ExtensionManager\Config\Stability;
use Flarum\ExtensionManager\Host\PhpOverride;
use Flarum\Foundation\Paths;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;

/**
 * Where Composer looks, what it will accept, and how it authenticates.
 *
 * 🚨 One controller for the three settings that decide whether Composer can find
 * anything at all. Without them the Extension Manager can update what is already installed
 * and nothing else — which is why the extension it replaces could not be removed.
 *
 * 🚨 No stored credential is ever in a response. The auth section reports which
 * hosts have one; the values are write-only by construction. See AuthTokens.
 */
class ConfigController implements RequestHandlerInterface
{
    public function __construct(private Paths $paths)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $method = $request->getMethod();
        $body = (array) $request->getParsedBody();
        $action = (string) Arr::get($body, 'action', '');

        try {
            if ($method === 'GET') {
                return new JsonResponse($this->state());
            }

            /*
             * The command-line PHP, for the hosts where looking for one cannot
             * work (open_basedir, an unusual layout). Refused unless it RUNS
             * and says it is the CLI, and the reason comes back as a key the
             * page translates.
             */
            if ($action === 'set-php') {
                $override = new PhpOverride($this->paths->storage);
                $path = trim((string) Arr::get($body, 'path', ''));

                if ($path !== '' && ($refusal = $override->refusal($path)) !== null) {
                    return new JsonResponse(['errorKey' => $refusal[0], 'errorParams' => $refusal[1]], 422);
                }

                $override->set($path === '' ? null : $path);

                return new JsonResponse(['php' => $override->get()]);
            }

            match ($action) {
                'add-repository' => $this->repositories()->add(
                    (string) Arr::get($body, 'type', 'vcs'),
                    (string) Arr::get($body, 'url', ''),
                    Arr::get($body, 'name')
                ),
                'remove-repository' => $this->repositories()->remove((string) Arr::get($body, 'url', '')),
                'set-stability' => $this->stability()->set(
                    (string) Arr::get($body, 'minimumStability', 'stable'),
                    (bool) Arr::get($body, 'preferStable', true)
                ),
                'set-auth' => $this->auth()->set(
                    (string) Arr::get($body, 'kind', ''),
                    (string) Arr::get($body, 'host', ''),
                    (string) Arr::get($body, 'secret', ''),
                    Arr::get($body, 'username')
                ),
                'remove-auth' => $this->auth()->remove(
                    (string) Arr::get($body, 'kind', ''),
                    (string) Arr::get($body, 'host', '')
                ),
                default => throw new RuntimeException(Lang::trans('unknown_action')),
            };
        } catch (RuntimeException $e) {
            /*
             * The message, not a generic failure. Every throw in the Config
             * classes is written to be read by the person who typed the value —
             * "that is not a hostname" is actionable, "invalid input" is not.
             */
            return new JsonResponse(['error' => $e->getMessage()], 422);
        }

        return new JsonResponse($this->state());
    }

    /** @return array<string,mixed> */
    private function state(): array
    {
        $stability = $this->stability();
        $current = $stability->current();

        return [
            'repositories' => $this->repositories()->all(),
            'stability' => $current + [
                'levels' => Stability::LEVELS,
            ],
            'auth' => [
                'stored' => $this->auth()->all(),
                'kinds' => AuthTokens::KINDS,
                'path' => 'auth.json',
            ],
            'types' => Repositories::TYPES,
            /*
             * 🚨 A change here does not re-resolve anything. Composer reads these
             * on the NEXT install or update, so saying so is the difference
             * between somebody understanding why nothing happened and somebody
             * concluding the screen is broken.
             */
            'note' => Lang::trans('config.takes_effect_next_time'),
        ];
    }

    private function repositories(): Repositories
    {
        return new Repositories(new JsonFile($this->paths->base.'/composer.json'));
    }

    private function stability(): Stability
    {
        return new Stability(new JsonFile($this->paths->base.'/composer.json'));
    }

    private function auth(): AuthTokens
    {
        return new AuthTokens(new JsonFile($this->paths->base.'/auth.json'));
    }
}
