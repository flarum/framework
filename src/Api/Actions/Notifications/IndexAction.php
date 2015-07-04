<?php namespace Flarum\Api\Actions\Notifications;

use Flarum\Core\Notifications\NotificationRepositoryInterface;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Api\Actions\SerializeCollectionAction;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

class IndexAction extends SerializeCollectionAction
{
    /**
     * @var NotificationRepositoryInterface
     */
    protected $notifications;

    /**
     * @inheritdoc
     */
    public static $serializer = 'Flarum\Api\Serializers\NotificationSerializer';

    /**
     * @inheritdoc
     */
    public static $include = [
        'sender' => true,
        'subject' => true,
        'subject.discussion' => true
    ];

    /**
     * @inheritdoc
     */
    public static $link = [];

    /**
     * @inheritdoc
     */
    public static $limitMax = 50;

    /**
     * @inheritdoc
     */
    public static $limit = 10;

    /**
     * @inheritdoc
     */
    public static $sortFields = [];

    /**
     * @inheritdoc
     */
    public static $sort;

    /**
     * Instantiate the action.
     *
     * @param NotificationRepositoryInterface $notifications
     */
    public function __construct(NotificationRepositoryInterface $notifications)
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
