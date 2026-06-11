<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit;

use Flarum\Api\Serializer\ForumSerializer;
use Flarum\Approval\Event as ApprovalEvent;
use Flarum\Audit\Extend\Audit;
use Flarum\Audit\Search\AuditSearcher;
use Flarum\Discussion\Event as DiscussionEvent;
use Flarum\Extend;
use Flarum\Extension\Event as ExtensionEvent;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Lock\Event as LockEvent;
use Flarum\Post\Event as PostEvent;
use Flarum\Sticky\Event as StickyEvent;
use Flarum\Suspend\Event as SuspendEvent;
use Flarum\Tags\Event as TagsEvent;

// Register usage examples and help for each search gambit so the audit browser can show
// clickable hints and a syntax help panel. Kept next to the gambit registration below;
// surfaced to the frontend via ForumAttributes. Descriptions are translation keys under
// flarum-audit.lib.browser.filters.<key>.
Search\AuditGambits::register('actor', 'actor:guest', 'flarum-audit.lib.browser.filters.actor');
Search\AuditGambits::register('user', 'user:', 'flarum-audit.lib.browser.filters.user');
Search\AuditGambits::register('action', 'action:user.logged_in', 'flarum-audit.lib.browser.filters.action');
Search\AuditGambits::register('client', 'client:session', 'flarum-audit.lib.browser.filters.client', ['session', 'api_key', 'access_token', 'cli', 'unknown']);
Search\AuditGambits::register('ip', 'ip:', 'flarum-audit.lib.browser.filters.ip');
Search\AuditGambits::register('discussion', 'discussion:', 'flarum-audit.lib.browser.filters.discussion');

return array_merge(
    [
        (new Extend\Frontend('forum'))
            ->js(__DIR__.'/js/dist/forum.js')
            ->css(__DIR__.'/less/forum.less'),

        (new Extend\Frontend('admin'))
            ->js(__DIR__.'/js/dist/admin.js')
            ->css(__DIR__.'/less/admin.less')
            ->content(Content\AdminPayload::class),

        (new Extend\Routes('api'))
            ->get('/audit/logs', 'flarum-audit.index', Controller\AuditIndexController::class),

        new Extend\Locales(__DIR__.'/locale'),

        (new Extend\Middleware('forum'))
            ->add(Middleware\SetLoggerActor::class),
        (new Extend\Middleware('admin'))
            ->add(Middleware\SetLoggerActor::class),
        (new Extend\Middleware('api'))
            ->add(Middleware\SetLoggerActor::class)
            ->add(Middleware\ExtendSetPermissionController::class)
            ->add(Middleware\LogPasswordResetAttempt::class),

        // Core integrations.

        (new Audit())
            ->group(null)
            ->register('cache_cleared')
            ->listen(ClearingCache::class, 'cache_cleared', fn () => []),

        (new Audit())
            ->group(null)
            ->register('extension.disabled', 'extension.enabled', 'extension.uninstalled')
            ->listen(ExtensionEvent\Disabled::class, 'extension.disabled', fn ($e) => ['package' => $e->extension->name])
            ->listen(ExtensionEvent\Enabled::class, 'extension.enabled', fn ($e) => ['package' => $e->extension->name])
            ->listen(ExtensionEvent\Uninstalled::class, 'extension.uninstalled', fn ($e) => ['package' => $e->extension->name]),

        (new Audit())
            ->group(null)
            ->register('discussion.created', 'discussion.deleted', 'discussion.hidden', 'discussion.renamed', 'discussion.restored')
            ->listen(DiscussionEvent\Started::class, 'discussion.created', fn ($e) => ['discussion_id' => $e->discussion->id])
            ->listen(DiscussionEvent\Deleted::class, 'discussion.deleted', fn ($e) => ['discussion_id' => $e->discussion->id])
            ->listen(DiscussionEvent\Hidden::class, 'discussion.hidden', fn ($e) => ['discussion_id' => $e->discussion->id])
            ->listen(DiscussionEvent\Restored::class, 'discussion.restored', fn ($e) => ['discussion_id' => $e->discussion->id])
            ->listen(DiscussionEvent\Renamed::class, 'discussion.renamed', fn ($e) => [
                'discussion_id' => $e->discussion->id,
                'old_title' => $e->oldTitle,
                'new_title' => $e->discussion->title,
            ]),

        (new Audit())
            ->group(null)
            ->register('post.created', 'post.deleted', 'post.hidden', 'post.restored', 'post.revised')
            ->listen(PostEvent\Deleted::class, 'post.deleted', fn ($e) => ['discussion_id' => $e->post->discussion->id, 'post_id' => $e->post->id])
            ->listen(PostEvent\Hidden::class, 'post.hidden', fn ($e) => ['discussion_id' => $e->post->discussion->id, 'post_id' => $e->post->id])
            ->listen(PostEvent\Restored::class, 'post.restored', fn ($e) => ['discussion_id' => $e->post->discussion->id, 'post_id' => $e->post->id])
            ->listen(PostEvent\Revised::class, 'post.revised', fn ($e) => ['discussion_id' => $e->post->discussion->id, 'post_id' => $e->post->id])
            // Not logging the first post. There's always going to be one created alongside the discussion.
            ->listen(PostEvent\Posted::class, 'post.created', fn ($e) => $e->post->number === 1 ? null : ['discussion_id' => $e->post->discussion->id, 'post_id' => $e->post->id]),

        (new Audit())
            ->group(null)
            // permission_changed and password_reset_attempted are logged from middleware;
            // setting_changed and the user.* actions come from the integrations below.
            ->register('permission_changed', 'setting_changed', 'user.password_reset_attempted')
            ->using(new Integration\CoreSettingIntegration())
            ->using(new Integration\CoreUserIntegration()),

        // First-party extension integrations. Each is gated on its extension being enabled so
        // that its events are only listened for — and its actions only advertised in the admin
        // settings — when the relevant extension is actually active.

        (new Extend\Conditional())
            ->whenExtensionEnabled('flarum-approval', fn () => [
                (new Audit())
                    ->group('flarum-approval')
                    ->listen(ApprovalEvent\PostWasApproved::class, 'post.approved', fn ($e) => [
                        'discussion_id' => $e->post->discussion->id,
                        'post_id' => $e->post->id,
                    ]),
            ])
            ->whenExtensionEnabled('flarum-flags', fn () => [
                (new Audit())
                    ->group('flarum-flags')
                    ->using(new Integration\FlagsIntegration()),
            ])
            ->whenExtensionEnabled('flarum-lock', fn () => [
                (new Audit())
                    ->group('flarum-lock')
                    ->listen(LockEvent\DiscussionWasLocked::class, 'discussion.locked', fn ($e) => ['discussion_id' => $e->discussion->id])
                    ->listen(LockEvent\DiscussionWasUnlocked::class, 'discussion.unlocked', fn ($e) => ['discussion_id' => $e->discussion->id]),
            ])
            ->whenExtensionEnabled('flarum-nicknames', fn () => [
                (new Audit())
                    ->group('flarum-nicknames')
                    ->using(new Integration\NicknamesIntegration()),
            ])
            ->whenExtensionEnabled('flarum-sticky', fn () => [
                (new Audit())
                    ->group('flarum-sticky')
                    ->listen(StickyEvent\DiscussionWasStickied::class, 'discussion.stickied', fn ($e) => ['discussion_id' => $e->discussion->id])
                    ->listen(StickyEvent\DiscussionWasUnstickied::class, 'discussion.unstickied', fn ($e) => ['discussion_id' => $e->discussion->id]),
            ])
            ->whenExtensionEnabled('flarum-suspend', fn () => [
                (new Audit())
                    ->group('flarum-suspend')
                    ->listen(SuspendEvent\Suspended::class, 'user.suspended', fn ($e) => array_merge(
                        ['user_id' => $e->user->id],
                        $e->user->suspended_until ? ['until' => $e->user->suspended_until->toIso8601String()] : []
                    ))
                    ->listen(SuspendEvent\Unsuspended::class, 'user.unsuspended', fn ($e) => ['user_id' => $e->user->id]),
            ])
            ->whenExtensionEnabled('flarum-tags', fn () => [
                (new Audit())
                    ->group('flarum-tags')
                    ->listen(TagsEvent\DiscussionWasTagged::class, 'discussion.tagged', fn ($e) => [
                        'discussion_id' => $e->discussion->id,
                        'old_tags' => \Illuminate\Support\Arr::pluck($e->oldTags, 'slug'),
                        // Can't use pre-loaded ->tags because of https://github.com/flarum/core/issues/2514
                        'new_tags' => $e->discussion->tags()->pluck('tags.slug')->all(),
                    ])
                    ->using(new Integration\TagsAdminIntegration()),
            ]),

        // Search.

        (new Extend\SimpleFlarumSearch(AuditSearcher::class))
            ->setFullTextGambit(Search\Gambits\NoOpFullTextGambit::class)
            ->addGambit(Search\Gambits\ActionGambit::class)
            ->addGambit(Search\Gambits\ActorGambit::class)
            ->addGambit(Search\Gambits\ClientGambit::class)
            ->addGambit(Search\Gambits\DiscussionGambit::class)
            ->addGambit(Search\Gambits\IpGambit::class)
            ->addGambit(Search\Gambits\UserGambit::class),

        (new Extend\Console())
            ->command(Console\ClearLogsCommand::class),

        (new Extend\ApiSerializer(ForumSerializer::class))
            ->attributes(ForumAttributes::class),

        (new Extend\ServiceProvider())
            ->register(LoggerServiceProvider::class),

        (new Extend\ModelVisibility(AuditLog::class))
            ->scope(Scope\View::class),

        new LogSelfEnabled(),
    ],
    // Audit integrations for third-party extensions live in a separate file because they
    // reference classes from extensions outside the Flarum monorepo. See the file header.
    require __DIR__.'/extend.thirdparty.php'
);
