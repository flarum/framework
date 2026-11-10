<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Tests\integration;

use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Signs in the user named by the X-Test-Actor header without a core session,
 * as an extension's own authentication (single sign-on, say) might.
 */
class SignsInWithoutSession implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $userId = $request->getHeaderLine('X-Test-Actor');

        if ($userId !== '' && ($user = User::query()->find($userId)) !== null) {
            $request = RequestUtil::withActor($request, $user);
        }

        return $handler->handle($request);
    }
}
