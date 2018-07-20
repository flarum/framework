<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum;

use Flarum\Http\Rememberer;
use Flarum\User\AuthToken;
use Flarum\User\User;
use Zend\Diactoros\Response\HtmlResponse;

class AuthenticationResponseFactory
{
    /**
     * @var Rememberer
     */
    protected $rememberer;

    /**
     * @param Rememberer $rememberer
     */
    public function __construct(Rememberer $rememberer)
    {
        $this->rememberer = $rememberer;
    }

    public function make(array $data): HtmlResponse
    {
        if (isset($data['suggestions']['username'])) {
            $data['suggestions']['username'] = $this->sanitizeUsername($data['suggestions']['username']);
        }

        if ($user = User::where($data['identification'])->first()) {
            $payload = ['authenticated' => true];
        } else {
            $token = $this->generateToken($data['identification'], $data['attributes'] ?? []);
            $payload = $this->buildPayload($token, $data['identification'], $data['suggestions'] ?? []);
        }

        $response = $this->makeResponse($payload);

        if ($user) {
            $response = $this->rememberer->rememberUser($response, $user->id);
        }

        return $response;
    }

    private function sanitizeUsername(string $username): string
    {
        return preg_replace('/[^a-z0-9-_]/i', '', $username);
    }

    private function generateToken(array $identification, array $attributes): AuthToken
    {
        $payload = array_merge($identification, $attributes);

        $token = AuthToken::generate($payload);
        $token->save();

        return $token;
    }

    private function buildPayload(AuthToken $token, array $identification, array $suggestions): array
    {
        $payload = array_merge($identification, $suggestions);

        $payload['token'] = $token->id;
        $payload['provided'] = array_keys($identification);

        return $payload;
    }

    private function makeResponse(array $payload): HtmlResponse
    {
        $content = sprintf(
            '<script>window.opener.app.authenticationComplete(%s); window.close();</script>',
            json_encode($payload)
        );

        return new HtmlResponse($content);
    }
}
