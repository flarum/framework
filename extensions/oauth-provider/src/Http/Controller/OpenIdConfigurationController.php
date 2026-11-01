<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Http\Controller;

use Flarum\OAuthProvider\Server\AuthorizationServerMetadata;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * OpenID Connect Discovery 1.0 §4 — `/.well-known/openid-configuration`.
 */
class OpenIdConfigurationController implements RequestHandlerInterface
{
    public function __construct(protected AuthorizationServerMetadata $metadata)
    {
    }

    public function handle(Request $request): ResponseInterface
    {
        return new JsonResponse($this->metadata->openId());
    }
}
