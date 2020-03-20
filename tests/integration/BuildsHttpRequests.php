<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration;

use Dflydev\FigCookies\SetCookie;
use Flarum\Http\AccessToken;
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
        $token = AccessToken::generate($userId);
        $token->save();

        return $req->withAddedHeader('Authorization', "Token {$token->token}");
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
}
