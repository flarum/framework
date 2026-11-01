<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp;

use Flarum\Extend;
use Flarum\Extension\ExtensionManager;
use Flarum\Http\Middleware\StartSession;
use Flarum\Http\UrlGenerator;
use Flarum\Mcp\Capability\McpContext;
use Flarum\Mcp\Extend\Mcp;
use Flarum\OAuthProvider\Extend\Resource as OAuthResource;
use Flarum\OAuthProvider\Extend\Scope;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\ServiceProvider())
        ->register(McpServiceProvider::class),

    new Extend\ApiResource(Api\Resource\CapabilityResource::class),

    (new Extend\Routes('forum'))
        ->post('/mcp', 'mcp', Http\McpController::class)
        ->get('/mcp', 'mcp.get', Http\McpController::class)
        ->delete('/mcp', 'mcp.delete', Http\McpController::class)
        ->get('/.well-known/oauth-protected-resource/mcp', 'mcp.resourceMetadata', Http\ResourceMetadataController::class)
        ->get('/.well-known/oauth-protected-resource', 'mcp.resourceMetadata.root', Http\ResourceMetadataController::class),

    new Http\OptionsRoute(),

    (new Extend\Middleware('forum'))
        ->insertBefore(StartSession::class, Http\WithoutSession::class),

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
        ->resourceTemplate(Resource\DiscussionPostsTemplate::class)
        ->resourceTemplate(Resource\DiscussionPostsBeforeTemplate::class)
        ->resourceTemplate(Resource\UserTemplate::class)
        ->prompt(Prompt\SummarizeDiscussionPrompt::class)
        ->prompt(Prompt\DraftReplyPrompt::class)

        ->hide('access-tokens')
        ->hide('mail-settings')
        ->hide('extension-readmes')
        ->hide('system-info')
        ->hide('mcp-capabilities')
        // Listing marks the actor's notifications seen; list_notifications reads without that.
        ->hideEndpoint('notifications', 'index')

        ->allowWrites('discussions', ['create', 'update', 'delete'])
        ->allowWrites('posts', ['create', 'update', 'delete'])
        ->allowWrites('users', ['update'])
        ->allowWrites('notifications', ['update'])
        // Credentials and privileges stay out of an assistant's reach, whatever
        // the actor may do: content it reads could ask it to change them.
        // Groups (and their permissions) are not writable at all.
        ->denyWriteFields('users', ['password', 'email', 'isEmailConfirmed'], ['groups'])

        // json-api-server has no resource-level description, so core's live here.
        ->describe('forums', 'The forum itself: its title, description, settings visible to you, and your global permissions. There is one, with ID 1.')
        ->describe('discussions', 'Discussions (topics). Each has a title, a first post and replies, and counts of comments and participants.')
        ->describe('posts', 'Posts in discussions. Comment posts hold user content; other types record events such as a rename.')
        ->describe('users', 'Members of the forum, with their public profile.')
        ->describe('groups', 'User groups, which carry permissions and badges.')
        ->describe('notifications', 'Your notifications. Set isRead to true to mark one as read.'),

    // Bundled integrations, through the same extender third parties use, each
    // registered on behalf of the extension it integrates.
    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-tags', fn () => [
            (new Mcp('flarum-tags'))
                ->tool(Integration\Tags\ListTagsTool::class)
                ->extendTool('search_discussions', schema: Integration\Tags\TagArguments::searchSchema(...), handler: Integration\Tags\TagArguments::searchHandler(...))
                ->extendTool('start_discussion', schema: Integration\Tags\TagArguments::startSchema(...), handler: Integration\Tags\TagArguments::startHandler(...))
                ->describe('tags', 'Tags that discussions are filed under. Primary tags are ordered and can have child tags; secondary tags are not. list_tags shows the tree.'),
        ])
        ->whenExtensionEnabled('flarum-likes', fn () => [
            (new Mcp('flarum-likes'))
                ->tool(Integration\Likes\LikePostTool::class)
                ->tool(Integration\Likes\UnlikePostTool::class),
        ])
        ->whenExtensionEnabled('flarum-subscriptions', fn () => [
            (new Mcp('flarum-subscriptions'))
                ->tool(Integration\Subscriptions\FollowDiscussionTool::class),
        ])
        ->whenExtensionEnabled('flarum-lock', fn () => [
            (new Mcp('flarum-lock'))
                ->instructions(new Integration\Lock\LockInstructions()),
        ])
        ->whenExtensionEnabled('flarum-sticky', fn () => [
            (new Mcp('flarum-sticky'))
                ->instructions(new Integration\Sticky\StickyInstructions()),
        ])
        ->whenExtensionEnabled('flarum-messages', fn () => [
            (new Mcp('flarum-messages'))
                ->tool(Integration\Messages\ListConversationsTool::class)
                ->tool(Integration\Messages\ReadConversationTool::class)
                ->tool(Integration\Messages\SendMessageTool::class)
                ->describe('dialogs', 'Your private conversations (dialogs) with other members. list_conversations and read_conversation read them.')
                ->describe('dialog-messages', 'Messages in private conversations. send_message sends one.'),
        ])
        ->whenExtensionEnabled('flarum-mentions', fn () => [
            (new Mcp('flarum-mentions'))
                ->instructions(new Integration\Mentions\MentionInstructions()),
        ])
        ->when(fn (ExtensionManager $extensions) => $extensions->isEnabled('flarum-mentions') && $extensions->isEnabled('flarum-tags'), fn () => [
            (new Mcp('flarum-mentions'))
                ->instructions('#tag-slug links a tag.'),
        ])
        ->whenExtensionEnabled('flarum-suspend', fn () => [
            // Core already allows these; the fields' own gates decide.
            (new Mcp('flarum-suspend'))
                ->allowWrites('users', ['update'])
                ->instructions(new Integration\Suspend\SuspendInstructions()),
        ])
        ->whenExtensionEnabled('flarum-approval', fn () => [
            (new Mcp('flarum-approval'))
                ->allowWrites('posts', ['update'])
                ->instructions(new Integration\Approval\ApprovalInstructions()),
        ]),
];
