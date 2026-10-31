<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp;

use Flarum\Extend;
use Flarum\Http\UrlGenerator;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Extend\Mcp;
use Flarum\OAuthProvider\Extend\Resource as OAuthResource;
use Flarum\OAuthProvider\Extend\Scope;

return [
    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\Routes('forum'))
        ->post('/mcp', 'mcp', Http\McpController::class)
        ->get('/mcp', 'mcp.get', Http\McpController::class)
        ->delete('/mcp', 'mcp.delete', Http\McpController::class)
        ->get('/.well-known/oauth-protected-resource/mcp', 'mcp.resourceMetadata', Http\ResourceMetadataController::class)
        ->get('/.well-known/oauth-protected-resource', 'mcp.resourceMetadata.root', Http\ResourceMetadataController::class),

    new Http\OptionsRoute(),

    // Safe because /mcp never authenticates from the session cookie.
    (new Extend\Csrf())
        ->exemptRoute('mcp')
        ->exemptRoute('mcp.delete'),

    new Scope(McpContext::SCOPE_READ, 'Read the forum as you through AI assistants and other MCP clients'),
    new Scope(McpContext::SCOPE_WRITE, 'Post and make changes as you through AI assistants and other MCP clients'),

    new OAuthResource(
        fn (UrlGenerator $url) => $url->to('forum')->route('mcp'),
        [McpContext::SCOPE_READ, McpContext::SCOPE_WRITE],
    ),

    (new Mcp())
        ->tool(Tool\Generic\DescribeApiTool::class)
        ->tool(Tool\Generic\ListResourcesTool::class)
        ->tool(Tool\Generic\GetResourceTool::class)
        ->tool(Tool\Generic\CreateResourceTool::class)
        ->tool(Tool\Generic\UpdateResourceTool::class)
        ->tool(Tool\Generic\DeleteResourceTool::class)
        ->tool(Tool\SearchDiscussionsTool::class)
        ->tool(Tool\ReadDiscussionTool::class)
        ->tool(Tool\GetUserTool::class)
        ->tool(Tool\WhoamiTool::class)
        ->tool(Tool\ListNotificationsTool::class)
        ->tool(Tool\StartDiscussionTool::class)
        ->tool(Tool\ReplyToDiscussionTool::class)
        ->tool(Tool\EditPostTool::class)
        ->tool(Tool\MarkNotificationsReadTool::class)
        ->resource(Resource\ForumOverviewResource::class)
        ->resourceTemplate(Resource\DiscussionTemplate::class)
        ->resourceTemplate(Resource\UserTemplate::class)
        ->prompt(Prompt\SummarizeDiscussionPrompt::class)
        ->prompt(Prompt\DraftReplyPrompt::class)

        ->hide('access-tokens')
        ->hide('mail-settings')
        ->hide('extension-readmes')
        ->hide('system-info')

        ->allowWrites('discussions', ['create', 'update', 'delete'])
        ->allowWrites('posts', ['create', 'update', 'delete'])
        ->allowWrites('users', ['update'])
        ->allowWrites('groups', ['create', 'update', 'delete'])
        ->allowWrites('notifications', ['update'])

        // json-api-server has no resource-level description, so core's live here.
        ->describe('forum', 'The forum itself: its title, description, settings visible to you, and your global permissions. There is one, with ID 1.')
        ->describe('discussions', 'Discussions (topics). Each has a title, a first post and replies, and counts of comments and participants.')
        ->describe('posts', 'Posts in discussions. Comment posts hold user content; other types record events such as a rename.')
        ->describe('users', 'Members of the forum, with their public profile.')
        ->describe('groups', 'User groups, which carry permissions and badges.')
        ->describe('notifications', 'Your notifications. Set isRead to true to mark one as read.'),
];
