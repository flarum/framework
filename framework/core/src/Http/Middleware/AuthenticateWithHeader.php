<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Closure;
use Flarum\Api\ApiKey;
use Flarum\Http\AccessToken;
use Flarum\Http\RequestUtil;
use Flarum\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithHeader implements IlluminateMiddlewareInterface
{
    const TOKEN_PREFIX = 'Token ';

    public function handle(Request $request, Closure $next): Response
    {
        $headerLine = $request->header('Authorization');

        if (is_array($headerLine)) {
            $headerLine = implode(',', $headerLine);
        }

        $parts = explode(';', $headerLine);

        if (isset($parts[0]) && Str::startsWith($parts[0], self::TOKEN_PREFIX)) {
            $id = substr($parts[0], strlen(self::TOKEN_PREFIX));

            if ($key = ApiKey::where('key', $id)->first()) {
                $key->touch();

                $userId = $parts[1] ?? '';
                $actor = $key->user ?? $this->getUser($userId);

                $request = $request->withAttribute('apiKey', $key);
                $request = $request->withAttribute('bypassThrottling', true);
            } elseif ($token = AccessToken::findValid($id)) {
                $token->touch(request: $request);

                $actor = $token->user;
            }

            if (isset($actor)) {
                $actor->updateLastSeen()->save();

                $request = RequestUtil::withActor($request, $actor);
                $request = $request->withAttribute('bypassCsrfToken', true);
                $request = $request->withoutAttribute('session');
            }
        }

        return $next($request);
    }

    private function getUser(string $string): ?User
    {
        $parts = explode('=', trim($string));

        if (isset($parts[0]) && $parts[0] === 'userId') {
            return User::find($parts[1]);
        }

        return null;
    }
}
