<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Api\Actions\Notifications;

use Flarum\Core\Notifications\NotificationRepository;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Api\Actions\SerializeCollectionAction;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

class IndexAction extends SerializeCollectionAction
{
    /**
     * @var NotificationRepository
     */
    protected $notifications;

    /**
     * @inheritdoc
     */
    public $serializer = 'Flarum\Api\Serializers\NotificationSerializer';

    /**
     * @inheritdoc
     */
    public $include = [
        'sender' => true,
        'subject' => true,
        'subject.discussion' => true
    ];

    /**
     * @inheritdoc
     */
    public $link = [];

    /**
     * @inheritdoc
     */
    public $limitMax = 50;

    /**
     * @inheritdoc
     */
    public $limit = 10;

    /**
     * @inheritdoc
     */
    public $sortFields = [];

    /**
     * @inheritdoc
     */
    public $sort;

    /**
     * Instantiate the action.
     *
     * @param NotificationRepository $notifications
     */
    public function __construct(NotificationRepository $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * Get the notification results, ready to be serialized and assigned to the
     * document response.
     *
     * @param JsonApiRequest $request
     * @param Document $document
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws PermissionDeniedException
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        $actor = $request->actor;

        if ($actor->isGuest()) {
            throw new PermissionDeniedException;
        }

        $actor->markNotificationsAsRead()->save();

        return $this->notifications->findByUser($actor, $request->limit, $request->offset)
            ->load($request->include);
    }
}
