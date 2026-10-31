<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tool\Support;

use Flarum\Mcp\Api\ApiResult;
use Flarum\Mcp\Capability\McpContext;

/**
 * The output of `get_user` and `flarum://users/{username}`: the profile
 * fields the actor may see.
 *
 * @internal
 */
class UserReader
{
    /**
     * Attributes that describe the actor's own view rather than the profile.
     */
    protected const array OMIT = ['avatarSrcset', 'preferences', 'markedAllAsReadAt', 'unreadNotificationCount', 'newNotificationCount', 'colorScheme'];

    /**
     * @return array<string, mixed>
     */
    public function read(McpContext $context, ?string $id = null, ?string $username = null): array
    {
        $result = $username !== null
            // `bySlug` resolves through the forum's user slug driver (username by default).
            ? $context->api->show('users', $username, ['include' => 'groups', 'bySlug' => '1'])
            : $context->api->show('users', (string) $id, ['include' => 'groups']);

        $document = Document::from($result);
        $user = $document->one();

        $profile = ['type' => 'users', 'id' => (string) ($user['id'] ?? '')];

        foreach (ApiResult::normaliseAttributes((array) ($user['attributes'] ?? [])) as $name => $value) {
            if (! in_array($name, static::OMIT, true) && ! str_starts_with((string) $name, 'can')) {
                $profile[$name] = $value;
            }
        }

        $slug = (string) ($profile['slug'] ?? $profile['username'] ?? '');
        if ($slug !== '') {
            $profile['url'] = $context->url->to('forum')->route('user', ['username' => $slug]);
        }

        $groups = [];
        foreach ((array) ($user['relationships']['groups']['data'] ?? []) as $identifier) {
            $group = $document->includedFor(['relationships' => ['group' => ['data' => $identifier]]], 'group');

            if ($group) {
                $groups[] = (string) Document::attribute($group, 'nameSingular');
            }
        }

        $profile['groups'] = $groups;

        return $profile;
    }
}
