<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Http\AccessToken;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class RememberFromCookie implements MiddlewareInterface
{
    public function process(Request $request, DelegateInterface $delegate)
    {
        $id = array_get($request->getCookieParams(), 'flarum_remember');

        if ($id) {
            $token = AccessToken::find($id);

            if ($token) {
                $token->touch();

                $session = $request->getAttribute('session');
                $session->set('user_id', $token->user_id);
            }
        }

        return $delegate->process($request);
    }
}
