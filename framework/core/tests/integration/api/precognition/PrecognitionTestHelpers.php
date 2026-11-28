<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\precognition;

use Flarum\Api\PrecognitionThrottler;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Cache\Repository as Cache;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @mixin \Flarum\Testing\integration\TestCase
 */
trait PrecognitionTestHelpers
{
    protected function precognitive(ServerRequestInterface $request, ?string $only = null): ServerRequestInterface
    {
        $request = $request->withHeader('Precognition', 'true');

        return $only === null ? $request : $request->withHeader('Precognition-Validate-Only', $only);
    }

    protected function jsonRequest(string $method, string $path, ?array $json = null, ?int $as = null, ?string $ip = null): ServerRequestInterface
    {
        if ($ip === null) {
            $request = $this->request($method, $path, array_filter([
                'json' => $json,
                'authenticatedAs' => $as,
            ], fn ($v) => $v !== null));
        } else {
            $request = new ServerRequest(['REMOTE_ADDR' => $ip], [], $path, $method);

            if ($json !== null) {
                $request = $this->requestWithJsonBody($request, $json);
            }

            if ($as !== null) {
                $request = $this->requestAsUser($request, $as);
            }
        }

        return $as === null ? $request->withAttribute('bypassCsrfToken', true) : $request;
    }

    protected function limitPrecognition(int $limit): void
    {
        $this->app()->getContainer()->singleton('flarum.api.precognition_throttler', function ($container) use ($limit) {
            return new PrecognitionThrottler(new RateLimiter($container->make(Cache::class)), $limit, 60);
        });
    }

    protected function errorCode(ResponseInterface $response): ?string
    {
        return json_decode((string) $response->getBody(), true)['errors'][0]['code'] ?? null;
    }
}
