<?php namespace Flarum\Api\Actions\Notifications;

use Flarum\Core\Repositories\NotificationRepositoryInterface;
use Flarum\Core\Support\Actor;
use Flarum\Core\Exceptions\PermissionDeniedException;
use Flarum\Api\Actions\BaseAction;
use Flarum\Api\Actions\ApiParams;
use Flarum\Api\Serializers\NotificationSerializer;

class IndexAction extends BaseAction
{
    /**
     * Instantiate the action.
     *
     * @param  \Flarum\Core\Search\Discussions\UserSearcher  $searcher
     */
    public function __construct(Actor $actor, NotificationRepositoryInterface $notifications)
    {
        $this->actor = $actor;
        $this->notifications = $notifications;
    }

    /**
     * Show a user's notifications feed.
     *
     * @return \Illuminate\Http\Response
     */
    protected function run(ApiParams $params)
    {
        $start = $params->start();
        $count = $params->count(10, 50);

        if (! $this->actor->isAuthenticated()) {
            throw new PermissionDeniedException;
        }

        $user = $this->actor->getUser();

        $notifications = $this->notifications->findByUser($user->id, $count, $start);

        $user->markNotificationsAsRead()->save();

        // Finally, we can set up the notification serializer and use it to create
        // a collection of notification results.
        $serializer = new NotificationSerializer(['sender', 'subject', 'subject.discussion']);
        $document = $this->document()->setData($serializer->collection($notifications));

        return $this->respondWithDocument($document);
    }
}
