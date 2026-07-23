<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Schema;
use Flarum\Bus\Dispatcher;
use Flarum\Discussion\Discussion;
use Flarum\Notification\Command\ReadNotification;
use Flarum\Notification\Notification;
use Flarum\Notification\NotificationRepository;
use Flarum\Post\Post;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Tobyz\JsonApiServer\Pagination\OffsetPagination;
use Tobyz\JsonApiServer\Schema\Field\Relationship;

/**
 * @extends AbstractDatabaseResource<Notification>
 */
class NotificationResource extends AbstractDatabaseResource
{
    protected bool $initialized = false;

    public function __construct(
        protected Dispatcher $bus,
        protected NotificationRepository $notifications,
        protected CacheRepository $cache,
    ) {
        $this->initialized = true;
    }

    public function type(): string
    {
        return 'notifications';
    }

    public function model(): string
    {
        return Notification::class;
    }

    public function scope(Builder $query, \Tobyz\JsonApiServer\Context $context): void
    {
        $query->where('user_id', $context->getActor()->id);
    }

    public function query(\Tobyz\JsonApiServer\Context $context): object
    {
        if ($context->listing(self::class)) {
            /** @var Endpoint\Index $endpoint */
            $endpoint = $context->endpoint;
            /** @var OffsetPagination $pagination */
            $pagination = ($endpoint->paginationResolver)($context);

            return $this->notifications->query($context->getActor(), $pagination->limit, $pagination->offset);
        }

        return parent::query($context);
    }

    public function endpoints(): array
    {
        // Eager-load the subject's discussion only when at least one subject
        // type actually exposes a `discussion` relationship. Gating on the
        // number of subject types instead would add an invalid
        // `subject.discussion` default include when the extra types have no such
        // relationship (e.g. a User-subject notification like flarum/mentions'
        // `userMentioned` on a forum with no Post-subject notification type),
        // which the include validator then rejects with a 400 on the endpoint's
        // own default.
        $defaultInclude = array_filter([
            'fromUser',
            'subject',
            $this->initialized && $this->subjectsHaveDiscussionRelationship()
                ? 'subject.discussion'
                : null,
        ]);

        return [
            Endpoint\Show::make()
                ->authenticated()
                ->defaultInclude($defaultInclude)
                ->eagerLoad('fromUser')
                ->eagerLoadWhere('subject', $this->eagerLoadSubject(...)),
            Endpoint\Update::make()
                ->authenticated(),
            Endpoint\Index::make()
                ->authenticated()
                ->before(function (Context $context) {
                    $actor = $context->getActor();
                    $actor->markNotificationsAsRead()->save();
                    // Invalidate new notification count cache since read_notifications_at changed
                    $this->cache->forget("user.{$actor->id}.new_notification_count");
                })
                ->defaultInclude($defaultInclude)
                ->eagerLoad('fromUser')
                ->eagerLoadWhere('subject', $this->eagerLoadSubject(...))
                ->paginate(),
        ];
    }

    /**
     * Eager-load the polymorphic subject's own `state`/`discussion.state` so the
     * notifications index does not issue a query per row for them (n+1).
     */
    protected function eagerLoadSubject(Relation $query): void
    {
        if ($query instanceof MorphTo) {
            $query->morphWith($this->subjectMorphWith());
        }
    }

    /**
     * The nested relationships to eager-load per subject model type.
     *
     * @return array<class-string, string[]>
     */
    protected function subjectMorphWith(): array
    {
        $map = [];

        foreach (array_unique((new Notification())->getSubjectModels()) as $class) {
            if (is_a($class, Discussion::class, true)) {
                $map[$class] = ['state'];
            } elseif (is_a($class, Post::class, true)) {
                $map[$class] = ['discussion.state'];
            }
        }

        return $map;
    }

    /**
     * Whether any registered notification subject type exposes an includable
     * `discussion` relationship, making `subject.discussion` a valid include.
     */
    protected function subjectsHaveDiscussionRelationship(): bool
    {
        foreach ($this->subjectTypes() as $type) {
            // typesForModels() yields null for a subject model that has no
            // registered API resource; there is nothing to inspect for those.
            if ($type === null) {
                continue;
            }

            $resource = $this->api->getResource($type);

            if (! $resource instanceof AbstractResource) {
                continue;
            }

            foreach ($resource->resolveFields() as $field) {
                if ($field instanceof Relationship && $field->includable && $field->name === 'discussion') {
                    return true;
                }
            }
        }

        return false;
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('contentType')
                ->property('type'),
            Schema\Arr::make('content')
                ->property('data'),
            Schema\DateTime::make('createdAt'),
            Schema\Boolean::make('isRead')
                ->writable()
                ->get(fn (Notification $notification) => (bool) $notification->read_at)
                ->set(function (Notification $notification, bool $_value, Context $context) {
                    $this->bus->dispatch(
                        new ReadNotification($notification->id, $context->getActor())
                    );

                    $notification->refresh();
                }),

            Schema\Relationship\ToOne::make('user')
                ->includable(),
            Schema\Relationship\ToOne::make('fromUser')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToOne::make('subject')
                ->collection($this->subjectTypes())
                ->includable(),
        ];
    }

    protected function subjectTypes(): array
    {
        return $this->api->typesForModels(
            (new Notification())->getSubjectModels()
        );
    }
}
