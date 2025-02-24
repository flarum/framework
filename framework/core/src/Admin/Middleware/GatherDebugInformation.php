<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Admin\Middleware;

use Flarum\Settings\SettingsRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;

class GatherDebugInformation implements Middleware
{
    public function __construct(protected SettingsRepositoryInterface $settings)
    {
    }

    public function process(Request $request, Handler $handler): Response
    {
        $this->upsertWebUser();

        $this->upsertOpCacheStatus();

        return $handler->handle($request);
    }

    /**
     * Read current web user, so we can compare that against CLI executions,
     * these often cause file permission issues.
     */
    public function upsertWebUser(): void
    {
        $user = $this->settings->get('core.debug.web_user');
        $currentUser = get_current_user();

        if ($user !== $currentUser) {
            $this->settings->set(
                'core.debug.web_user',
                $currentUser
            );
        }
    }

    /**
     * Read the opcache situation, this is only visible in web.
     * Cli has opcache disabled by default.
     */
    public function upsertOpCacheStatus(): void
    {
        $opcache = $this->settings->get('core.debug.opcache');
        $opcacheStatus = function_exists('opcache_get_configuration') && opcache_get_configuration() !== false ? 'on' : 'off';

        if ($opcache !== $opcacheStatus) {
            $this->settings->set(
                'core.debug.opcache',
                $opcacheStatus
            );
        }
    }
}
