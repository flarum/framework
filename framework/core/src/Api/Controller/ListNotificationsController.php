<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\NotificationSerializer;
use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\Notification\NotificationRepository;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListNotificationsController extends AbstractListController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = NotificationSerializer::class;

    /**
     * {@inheritdoc}
     */
    public $include = [
        'fromUser',
        'subject',
        'subject.discussion'
    ];

    /**
     * @var NotificationRepository
     */
    protected $notifications;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @param NotificationRepository $notifications
     * @param UrlGenerator $url
     */
    public function __construct(NotificationRepository $notifications, UrlGenerator $url)
    {
        $this->notifications = $notifications;
        $this->url = $url;
    }

    /**
     * {@inheritdoc}
     */
    protected function data(ServerRequestInterface $request, Document $document)
    {
        $actor = RequestUtil::getActor($request);

        $actor->assertRegistered();

        $actor->markNotificationsAsRead()->save();

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $include = $this->extractInclude($request);

        if (! in_array('subject', $include)) {
            $include[] = 'subject';
        }

        $notifications = $this->notifications->findByUser($actor, $limit + 1, $offset);

        $this->loadRelations($notifications, array_diff($include, ['subject.discussion']), $request);

        $notifications = $notifications->all();

        $areMoreResults = false;

        if (count($notifications) > $limit) {
            array_pop($notifications);
            $areMoreResults = true;
        }

        $document->addPaginationLinks(
            $this->url->to('api')->route('notifications.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $areMoreResults ? null : 0
        );

        if (in_array('subject.discussion', $include)) {
            $this->loadSubjectDiscussions($notifications);
        }

        return $notifications;
    }

    /**
     * @param \Flarum\Notification\Notification[] $notifications
     */
    private function loadSubjectDiscussions(array $notifications)
    {
        $ids = [];

        foreach ($notifications as $notification) {
            if ($notification->subject && ($discussionId = $notification->subject->getAttribute('discussion_id'))) {
                $ids[] = $discussionId;
            }
        }

        $discussions = Discussion::query()->find(array_unique($ids));

        foreach ($notifications as $notification) {
            if ($notification->subject && ($discussionId = $notification->subject->getAttribute('discussion_id'))) {
                $notification->subject->setRelation('discussion', $discussions->find($discussionId));
            }
        }
    }
}
