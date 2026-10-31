<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Capability;

use Flarum\Http\UrlGenerator;
use Flarum\Mcp\Api\ApiClient;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * What a capability gets to act with during one MCP request.
 */
class McpContext
{
    public const string SCOPE_READ = 'mcp:read';
    public const string SCOPE_WRITE = 'mcp:write';

    /**
     * @param list<string> $scopes
     */
    public function __construct(
        public readonly User $actor,
        public readonly array $scopes,
        public readonly ApiClient $api,
        public readonly UrlGenerator $url,
        public readonly TranslatorInterface $translator,
        public readonly SettingsRepositoryInterface $settings,
        public readonly ServerRequestInterface $request,
    ) {
    }

    public function forumUrl(string $path = ''): string
    {
        $path = ltrim($path, '/');

        return $path === ''
            ? $this->url->to('forum')->base()
            : $this->url->to('forum')->path($path);
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }
}
