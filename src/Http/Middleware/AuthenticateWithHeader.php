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

use Flarum\Api\ApiKey;
use Flarum\Http\AccessToken;
use Flarum\User\User;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthenticateWithHeader implements MiddlewareInterface
{
    const TOKEN_PREFIX = 'Token ';

    public function process(Request $request, DelegateInterface $delegate)
    {
        $headerLine = $request->getHeaderLine('authorization');

        $parts = explode(';', $headerLine);

        if (isset($parts[0]) && starts_with($parts[0], self::TOKEN_PREFIX)) {
            $id = substr($parts[0], strlen(self::TOKEN_PREFIX));

            if (isset($parts[1])) {
                if ($key = ApiKey::find($id)) {
                    $actor = $this->getUser($parts[1]);

                    $request = $request->withAttribute('apiKey', $key);
                    $request = $request->withAttribute('bypassFloodgate', true);
                }
            } elseif ($token = AccessToken::find($id)) {
                $token->touch();

                $actor = $token->user;
            }

            if (isset($actor)) {
                $request = $request->withAttribute('actor', $actor);
                $request = $request->withoutAttribute('session');
            }
        }

        return $delegate->process($request);
    }

    private function getUser($string)
    {
        $parts = explode('=', trim($string));

        if (isset($parts[0]) && $parts[0] === 'userId') {
            return User::find($parts[1]);
        }
    }
}
