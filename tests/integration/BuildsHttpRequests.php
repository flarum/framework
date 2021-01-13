<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Laminas\Diactoros\CallbackStream;
use Psr\Http\Message\ResponseInterface as Response;
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
            'lifetime_seconds' => 3600
        ]);

        return $req->withAddedHeader('Authorization', "Token {$token}");
    }

    protected function requestWithCookiesFrom(Request $req, Response $previous): Request
    {
        $cookies = array_reduce(
            $previous->getHeader('Set-Cookie'),
            function ($memo, $setCookieString) {
                preg_match('~^(?<name>[^=]+)=(?<value>[^;]+)~', $setCookieString, $m);

                $memo[$m['name']] = $m['value'];

                return $memo;
            },
            []
        );

        return $req->withCookieParams($cookies);
    }
}
