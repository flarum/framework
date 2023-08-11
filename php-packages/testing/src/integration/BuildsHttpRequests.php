<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\integration;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * A collection of helpers for building PSR-7 requests for integration tests.
 */
trait BuildsHttpRequests
{
    protected function requestWithJsonBody(Request $req, array $json): Request
    {
        $req->headers->set('Content-Type', 'application/json');
        $req->setJson(new ParameterBag($json));

        return $req;
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
            'type' => 'session'
        ]);

        $req->headers->set('Authorization', "Token {$token}");

        // We save the token as an attribute so that we can retrieve it for test purposes.
        $req->attributes->set('tests_token', $token);

        return $req;
    }

    protected function requestWithCookiesFrom(Request $req, Response $previous): Request
    {
        $cookies = array_reduce(
            $previous->headers->all('Set-Cookie'),
            function ($memo, $setCookieString) {
                $cookie = Cookie::fromString($setCookieString);
                $memo[$cookie->getName()] = $cookie->getValue();

                return $memo;
            },
            []
        );

        $req->cookies->add($cookies);

        return $req;
    }

    protected function requestWithCsrfToken(Request $request): Request
    {
        $initial = $this->send(
            $this->request('GET', '/')
        );

        $token = $initial->headers->get('X-CSRF-Token');
        $request->headers->set('X-CSRF-Token', $token);

        return $this->requestWithCookiesFrom($request, $initial);
    }
}
