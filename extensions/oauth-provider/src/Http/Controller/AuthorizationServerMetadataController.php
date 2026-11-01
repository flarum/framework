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
 * OAuth 2.0 Authorization Server Metadata (RFC 8414) — `/.well-known/oauth-authorization-server`.
 */
class AuthorizationServerMetadataController implements RequestHandlerInterface
{
    public function __construct(protected AuthorizationServerMetadata $metadata)
    {
    }

    public function handle(Request $request): ResponseInterface
    {
        return new JsonResponse($this->metadata->oauth());
    }
}
