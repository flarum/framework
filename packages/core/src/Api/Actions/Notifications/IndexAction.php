<?php namespace Flarum\Api\Actions\Notifications;

use Flarum\Core\Repositories\NotificationRepositoryInterface;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Api\Actions\SerializeCollectionAction;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

class IndexAction extends SerializeCollectionAction
{
    /**
     * @var \Flarum\Core\Repositories\NotificationRepositoryInterface
     */
    protected $notifications;

    /**
     * The name of the serializer class to output results with.
     *
     * @var string
     */
    public static $serializer = 'Flarum\Api\Serializers\NotificationSerializer';

    /**
     * The relations that are included by default.
     *
     * @var array
     */
    public static $include = [
        'sender' => true,
        'subject' => true,
        'subject.discussion' => true
    ];

    /**
     * The maximum number of records that can be requested.
     *
     * @var integer
     */
    public static $limitMax = 50;

    /**
     * The number of records included by default.
     *
     * @var integer
     */
    public static $limit = 10;

    /**
     * Instantiate the action.
     *
     * @param \Flarum\Core\Repositories\NotificationRepositoryInterface $notifications
     */
    public function __construct(NotificationRepositoryInterface $notifications)
    {
        $this->notifications = $notifications;
    }

    /**
     * Get the notification results, ready to be serialized and assigned to the
     * document response.
     *
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Tobscure\JsonApi\Document $document
     * @return \Illuminate\Database\Eloquent\Collection
     * @throws PermissionDeniedException
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        if (! $request->actor->isAuthenticated()) {
            throw new PermissionDeniedException;
        }

        $user = $request->actor->getUser();

        $user->markNotificationsAsRead()->save();

        return $this->notifications->findByUser($user, $request->limit, $request->offset)
            ->load($request->include);
    }
}
