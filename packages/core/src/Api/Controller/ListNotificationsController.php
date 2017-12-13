<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\UrlGenerator;
use Flarum\Core\Discussion;
use Flarum\Core\Exception\PermissionDeniedException;
use Flarum\Core\Repository\NotificationRepository;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ListNotificationsController extends AbstractCollectionController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = 'Flarum\Api\Serializer\NotificationSerializer';

    /**
     * {@inheritdoc}
     */
    public $include = [
        'sender',
        'subject',
        'subject.discussion'
    ];

    /**
     * {@inheritdoc}
     */
    public $limit = 10;

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
        $actor = $request->getAttribute('actor');

        if ($actor->isGuest()) {
            throw new PermissionDeniedException;
        }

        $actor->markNotificationsAsRead()->save();

        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        $include = $this->extractInclude($request);

        if (! in_array('subject', $include)) {
            $include[] = 'subject';
        }

        $notifications = $this->notifications->findByUser($actor, $limit + 1, $offset)
            ->load(array_diff($include, ['subject.discussion']))
            ->all();

        $areMoreResults = false;

        if (count($notifications) > $limit) {
            array_pop($notifications);
            $areMoreResults = true;
        }

        $document->addPaginationLinks(
            $this->url->toRoute('notifications.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $areMoreResults ? null : 0
        );

        $notifications = array_filter($notifications, function ($notification) {
            return ! $notification->subjectModel || $notification->subject;
        });

        if (in_array('subject.discussion', $include)) {
            $this->loadSubjectDiscussions($notifications);
        }

        return $notifications;
    }

    /**
     * @param \Flarum\Core\Notification[] $notifications
     */
    private function loadSubjectDiscussions(array $notifications)
    {
        $ids = [];

        foreach ($notifications as $notification) {
            if ($notification->subject && $notification->subject->discussion_id) {
                $ids[] = $notification->subject->discussion_id;
            }
        }

        $discussions = Discussion::find(array_unique($ids));

        foreach ($notifications as $notification) {
            if ($notification->subject && $notification->subject->discussion_id) {
                $notification->subject->setRelation('discussion', $discussions->find($notification->subject->discussion_id));
            }
        }
    }
}
