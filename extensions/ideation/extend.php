<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Api\Context;
use Flarum\Api\Resource\DiscussionResource;
use Flarum\Api\Resource\ForumResource;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\Extension\Extension;
use Flarum\Ideation\Access\DiscussionPolicy;
use Flarum\Ideation\Api\DiscussionFields;
use Flarum\Ideation\Event\StatusChanged;
use Flarum\Ideation\GitHub;
use Flarum\Ideation\Listener;
use Flarum\Ideation\Notification\IdeaShippedBlueprint;
use Flarum\Ideation\Post\IdeationStatusPost;
use Flarum\Ideation\Search\IdeationFilter;
use Flarum\Search\Database\DatabaseSearchDriver;
use Flarum\User\Event\Deleting;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Contracts\Container\Container;
use Tobyz\JsonApiServer\Schema\CustomSort;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->jsDirectory(__DIR__.'/js/dist/forum')
        ->css(__DIR__.'/less/forum.less')
        ->route('/roadmap', 'ideation'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\View())
        ->namespace('flarum-ideation', __DIR__.'/views'),

    // Also how PHPStan learns these columns of core's discussions table.
    (new Extend\Model(Discussion::class))
        ->cast('ideation_status', 'string')
        ->cast('ideation_votes', 'integer')
        ->cast('ideation_merged_into_id', 'integer')
        ->cast('ideation_status_changed_at', 'datetime'),

    (new Extend\Policy())
        ->modelPolicy(Discussion::class, DiscussionPolicy::class),

    // Two booleans in the boot payload, read from permissions already loaded.
    (new Extend\ApiResource(ForumResource::class))
        ->fields(fn () => [
            Schema\Boolean::make('ideationCanPost')
                ->get(fn ($forum, Context $context) => $context->getActor()->hasPermission('ideation.postIdeas')),
            // Whether the actor moderates ideas anywhere, globally or in a tag;
            // each discussion's `canIdeationModerate` says where.
            Schema\Boolean::make('ideationCanModerate')
                ->get(fn ($forum, Context $context) => $context->getActor()->hasPermissionLike('discussion.ideationModerate')),
            // Whether ideas show GitHub links at all; the token, secret and
            // repository list never leave the server.
            Schema\Boolean::make('ideationGithub')
                ->get(fn () => resolve(GitHub\Config::class)->enabled()),
        ]),

    (new Extend\ApiResource(DiscussionResource::class))
        ->fields(DiscussionFields::class)
        ->sorts(fn () => [
            // Most votes first; among equals, the newer idea, so a page break
            // never shows one idea twice or skips one.
            CustomSort::make('ideationVotes', function ($query, string $direction) {
                $query->orderBy('discussions.ideation_votes', $direction)
                    ->orderBy('discussions.id', $direction);
            }),
            SortColumn::make('ideationStatusChangedAt')
                ->column('discussions.ideation_status_changed_at'),
        ]),

    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addFilter(DiscussionSearcher::class, IdeationFilter::class),

    (new Extend\Post())
        ->type(IdeationStatusPost::class),

    (new Extend\Notification())
        ->type(IdeaShippedBlueprint::class, ['alert', 'email']),

    // The optional GitHub integration: a signed webhook, and an hourly
    // capped refresh for anything the webhook missed.
    (new Extend\Routes('api'))
        ->post('/ideation/github/webhook', 'ideation.github.webhook', GitHub\WebhookController::class),

    (new Extend\Csrf())
        ->exemptRoute('ideation.github.webhook'),

    (new Extend\Console())
        ->command(GitHub\SyncCommand::class)
        ->schedule(GitHub\SyncCommand::class, function (Event $event): void {
            $event->hourly()->withoutOverlapping();
        }),

    // The GitHub client, unless something (a test's fake) is bound already.
    new class implements Extend\ExtenderInterface {
        public function extend(Container $container, ?Extension $extension = null): void
        {
            $container->bindIf(GitHub\Client::class, GitHub\HttpClient::class);
        }
    },

    (new Extend\Settings())
        ->default(GitHub\Config::PREFIX.'enabled', false)
        ->default(GitHub\Config::PREFIX.'repos', '')
        ->default(GitHub\Config::PREFIX.'token', '')
        ->default(GitHub\Config::PREFIX.'secret', '')
        ->default(GitHub\Config::PREFIX.'auto_progress', false)
        ->default(GitHub\Config::PREFIX.'auto_ship', false),

    (new Extend\Event())
        ->listen(StatusChanged::class, Listener\RecordStatusChange::class)
        ->listen(Deleting::class, Listener\ForgetDeletedUserVotes::class),
];
