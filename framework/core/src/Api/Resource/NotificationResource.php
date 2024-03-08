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
use Flarum\Notification\Command\ReadNotification;
use Flarum\Notification\Notification;
use Flarum\Notification\NotificationRepository;
use Tobyz\JsonApiServer\Pagination\Pagination;

class NotificationResource extends AbstractDatabaseResource
{
    public function __construct(
        protected Dispatcher $bus,
        protected NotificationRepository $notifications,
    ) {
    }

    public function type(): string
    {
        return 'notifications';
    }

    public function model(): string
    {
        return Notification::class;
    }

    public function query(\Tobyz\JsonApiServer\Context $context): object
    {
        if ($context->listing(self::class)) {
            /** @var Pagination $pagination */
            $pagination = ($context->endpoint->paginationResolver)($context);

            return $this->notifications->query($context->getActor(), $pagination->limit, $pagination->offset);
        }

        return parent::query($context);
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Update::make()
                ->authenticated(),
            Endpoint\Index::make()
                ->authenticated()
                ->before(function (Context $context) {
                    $context->getActor()->markNotificationsAsRead()->save();
                })
                ->defaultInclude([
                    'fromUser',
                    'subject',
                    'subject.discussion'
                ])
                ->paginate(),
        ];
    }

    public function fields(): array
    {
        $subjectTypes = $this->api->typesForModels(
            (new Notification())->getSubjectModels()
        );

        return [
            Schema\Str::make('contentType')
                ->property('type'),
            Schema\Arr::make('content')
                ->property('data'),
            Schema\DateTime::make('createdAt'),
            Schema\Boolean::make('isRead')
                ->writable()
                ->get(fn (Notification $notification) => (bool) $notification->read_at)
                ->set(function (Notification $notification, Context $context) {
                    $this->bus->dispatch(
                        new ReadNotification($notification->id, $context->getActor())
                    );
                }),

            Schema\Relationship\ToOne::make('user')
                ->includable(),
            Schema\Relationship\ToOne::make('fromUser')
                ->type('users')
                ->includable(),
            Schema\Relationship\ToOne::make('subject')
                ->collection($subjectTypes)
                ->includable(),
        ];
    }
}
