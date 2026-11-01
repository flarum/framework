<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Http;

use Flarum\Http\UrlGenerator;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Settings\SettingsRepositoryInterface;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * OAuth 2.0 Protected Resource Metadata (RFC 9728) for the MCP endpoint.
 */
class ResourceMetadataController implements RequestHandlerInterface
{
    public function __construct(
        protected UrlGenerator $url,
        protected SettingsRepositoryInterface $settings,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $forum = $this->url->to('forum');

        return new JsonResponse([
            'resource' => $forum->route('mcp'),
            'authorization_servers' => [rtrim($forum->base(), '/')],
            'scopes_supported' => [McpContext::SCOPE_READ, McpContext::SCOPE_WRITE],
            'bearer_methods_supported' => ['header'],
            'resource_name' => (string) $this->settings->get('forum_title'),
        ], 200, ['Cache-Control' => 'public, max-age=3600']);
    }
}
