<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\integration;

use Carbon\Carbon;
use Dflydev\FigCookies\SetCookie;
use Flarum\Http\CookieFactory;
use Illuminate\Support\Str;
use Laminas\Diactoros\CallbackStream;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * A collection of helpers for building PSR-7 requests for integration tests.
 */
trait BuildsHttpRequests
{
    protected function requestWithJsonBody(Request $req, array $json): Request
    {
        return $req
            ->withHeader('Content-Type', 'application/json')
            ->withBody(
                new CallbackStream(function () use ($json) {
                    return json_encode($json);
                })
            );
    }

    protected function requestAsUser(Request $req, int $userId): Request
    {
        $token = Str::random(40);

        /**
         * We insert this directly instead of via `prepareDatabase`
         * so that requests can be created/sent after the app is booted.
         */
        $this->database()->table('access_tokens')->insert([
            'token' => $token,
            'user_id' => $userId,
            'created_at' => Carbon::now()->toDateTimeString(),
            'last_activity_at' => Carbon::now()->toDateTimeString(),
            'type' => 'session_remember'
        ]);

        $cookies = $this->app()->getContainer()->make(CookieFactory::class);

        return $req
            ->withAttribute('bypassCsrfToken', true)
            ->withCookieParams([$cookies->getName('remember') => $token])
            // We save the token as an attribute so that we can retrieve it for test purposes.
            ->withAttribute('tests_token', $token);
    }

    protected function requestWithCookiesFrom(Request $req, Response $previous): Request
    {
        $cookies = array_reduce(
            $previous->getHeader('Set-Cookie'),
            function ($memo, $setCookieString) {
                $setCookie = SetCookie::fromSetCookieString($setCookieString);
                $memo[$setCookie->getName()] = $setCookie->getValue();

                return $memo;
            },
            []
        );

        return $req->withCookieParams($cookies);
    }

    protected function requestWithCsrfToken(ServerRequestInterface $request): ServerRequestInterface
    {
        $initial = $this->send(
            $this->request('GET', '/')
        );

        $token = $initial->getHeaderLine('X-CSRF-Token');

        return $this->requestWithCookiesFrom($request->withHeader('X-CSRF-Token', $token), $initial);
    }
}
