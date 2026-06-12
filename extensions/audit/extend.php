<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit;

use Flarum\Api\Resource\ForumResource;
use Flarum\Audit\Extend\Audit;
use Flarum\Audit\Search\AuditSearcher;
use Flarum\Discussion\Event as DiscussionEvent;
use Flarum\Extend;
use Flarum\Extension\Event as ExtensionEvent;
use Flarum\Foundation\Event\ClearingCache;
use Flarum\Group\Event as GroupEvent;
use Flarum\Http\Event\DeveloperTokenCreated;
use Flarum\Post\Event as PostEvent;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\Settings\Event as SettingsEvent;

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
            ->jsDirectory(__DIR__.'/js/dist/forum')
            ->css(__DIR__.'/less/forum.less'),

        (new Extend\Frontend('common'))
            ->jsDirectory(__DIR__.'/js/dist/common'),

        (new Extend\Frontend('admin'))
            ->js(__DIR__.'/js/dist/admin.js')
            ->jsDirectory(__DIR__.'/js/dist/admin')
            ->css(__DIR__.'/less/admin.less')
            ->content(Content\AdminPayload::class),

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
            ->listen(ClearingCache::class, 'cache_cleared', function () {
                return [];
            }),

        (new Audit())
            ->group(null)
            ->register('extension.disabled', 'extension.enabled', 'extension.uninstalled')
            ->listen(ExtensionEvent\Disabled::class, 'extension.disabled', function ($e) {
                return ['package' => $e->extension->name];
            })
            ->listen(ExtensionEvent\Enabled::class, 'extension.enabled', function ($e) {
                return ['package' => $e->extension->name];
            })
            ->listen(ExtensionEvent\Uninstalled::class, 'extension.uninstalled', function ($e) {
                return ['package' => $e->extension->name];
            }),

        (new Audit())
            ->group(null)
            ->register('discussion.created', 'discussion.deleted', 'discussion.hidden', 'discussion.renamed', 'discussion.restored')
            ->listen(DiscussionEvent\Started::class, 'discussion.created', function ($e) {
                return ['discussion_id' => $e->discussion->id];
            })
            ->listen(DiscussionEvent\Deleted::class, 'discussion.deleted', function ($e) {
                return ['discussion_id' => $e->discussion->id];
            })
            ->listen(DiscussionEvent\Hidden::class, 'discussion.hidden', function ($e) {
                return ['discussion_id' => $e->discussion->id];
            })
            ->listen(DiscussionEvent\Restored::class, 'discussion.restored', function ($e) {
                return ['discussion_id' => $e->discussion->id];
            })
            ->listen(DiscussionEvent\Renamed::class, 'discussion.renamed', function ($e) {
                return [
                    'discussion_id' => $e->discussion->id,
                    'old_title' => $e->oldTitle,
                    'new_title' => $e->discussion->title,
                ];
            }),

        (new Audit())
            ->group(null)
            ->register('post.created', 'post.deleted', 'post.hidden', 'post.restored', 'post.revised')
            ->listen(PostEvent\Deleted::class, 'post.deleted', function ($e) {
                return ['discussion_id' => $e->post->discussion->id, 'post_id' => $e->post->id];
            })
            ->listen(PostEvent\Hidden::class, 'post.hidden', function ($e) {
                return ['discussion_id' => $e->post->discussion->id, 'post_id' => $e->post->id];
            })
            ->listen(PostEvent\Restored::class, 'post.restored', function ($e) {
                return ['discussion_id' => $e->post->discussion->id, 'post_id' => $e->post->id];
            })
            ->listen(PostEvent\Revised::class, 'post.revised', function ($e) {
                return ['discussion_id' => $e->post->discussion->id, 'post_id' => $e->post->id];
            })
            // Not logging the first post. There's always going to be one created alongside the discussion.
            ->listen(PostEvent\Posted::class, 'post.created', function ($e) {
                return $e->post->number === 1 ? null : ['discussion_id' => $e->post->discussion->id, 'post_id' => $e->post->id];
            }),

        (new Audit())
            ->group(null)
            // permission_changed and password_reset_attempted are logged from middleware;
            // setting_changed and the user.* actions come from the integrations below.
            ->register('permission_changed', 'setting_changed', 'user.password_reset_attempted')
            ->using(new Integration\CoreSettingIntegration())
            ->using(new Integration\CoreUserIntegration()),

        (new Audit())
            ->group(null)
            ->register('group.created', 'group.renamed', 'group.deleted')
            // Group CRUD is request-scoped, so the acting admin is the ambient logger actor
            // (the events themselves carry no actor — same as the discussion/post listeners above).
            ->listen(GroupEvent\Created::class, 'group.created', function ($e) {
                return ['group_id' => $e->group->id, 'name' => $e->group->name_singular];
            })
            ->listen(GroupEvent\Renamed::class, 'group.renamed', function ($e) {
                return [
                    'group_id' => $e->group->id,
                    'old_name' => $e->oldNameSingular,
                    'new_name' => $e->group->name_singular,
                ];
            })
            ->listen(GroupEvent\Deleted::class, 'group.deleted', function ($e) {
                return ['group_id' => $e->group->id, 'name' => $e->group->name_singular];
            }),

        (new Audit())
            ->group(null)
            ->register('developer_token_created')
            // Logs the issuance of a long-lived developer API token. The raw token value is
            // deliberately never logged — only the owner and the human-readable title.
            ->listen(DeveloperTokenCreated::class, 'developer_token_created', function ($e) {
                return ['user_id' => $e->token->user_id, 'title' => $e->token->title];
            }),

        (new Audit())
            ->group(null)
            ->register('settings_reset')
            ->listen(SettingsEvent\Reset::class, 'settings_reset', function ($e) {
                return ['extension' => $e->extensionId, 'keys' => $e->keys];
            }),

        // Search.

        (new Extend\SearchDriver(DatabaseSearchDriver::class))
            ->addSearcher(AuditLog::class, AuditSearcher::class)
            ->setFulltext(AuditSearcher::class, Search\FulltextFilter::class)
            ->addFilter(AuditSearcher::class, Search\Filter\ActionFilter::class)
            ->addFilter(AuditSearcher::class, Search\Filter\ActorFilter::class)
            ->addFilter(AuditSearcher::class, Search\Filter\ClientFilter::class)
            ->addFilter(AuditSearcher::class, Search\Filter\DiscussionFilter::class)
            ->addFilter(AuditSearcher::class, Search\Filter\IpFilter::class)
            ->addFilter(AuditSearcher::class, Search\Filter\UserFilter::class),

        (new Extend\Console())
            ->command(Console\ClearLogsCommand::class),

        new Extend\ApiResource(Api\Resource\AuditLogResource::class),

        (new Extend\ApiResource(ForumResource::class))
            ->fields(ForumAttributes::class),

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
