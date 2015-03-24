<?php namespace Flarum\Api\Actions\Activity;

use Flarum\Core\Repositories\UserRepositoryInterface;
use Flarum\Core\Repositories\ActivityRepositoryInterface;
use Flarum\Core\Support\Actor;
use Flarum\Api\Actions\BaseAction;
use Flarum\Api\Actions\ApiParams;
use Flarum\Api\Serializers\ActivitySerializer;

class IndexAction extends BaseAction
{
    /**
     * Instantiate the action.
     *
     * @param  \Flarum\Core\Search\Discussions\UserSearcher  $searcher
     */
    public function __construct(Actor $actor, UserRepositoryInterface $users, ActivityRepositoryInterface $activity)
    {
        $this->actor = $actor;
        $this->users = $users;
        $this->activity = $activity;
    }

    /**
     * Show a user's activity feed.
     *
     * @return \Illuminate\Http\Response
     */
    protected function run(ApiParams $params)
    {
        $start = $params->start();
        $count = $params->count(20, 50);
        $type  = $params->get('type');
        $id    = $params->get('users');

        $user = $this->users->findOrFail($id, $this->actor->getUser());

        $activity = $this->activity->findByUser($user->id, $this->actor->getUser(), $count, $start, $type);

        // Finally, we can set up the activity serializer and use it to create
        // a collection of activity results.
        $serializer = new ActivitySerializer(['sender', 'post', 'post.discussion', 'post.user', 'post.discussion.startUser', 'post.discussion.lastUser'], ['user']);
        $document = $this->document()->setData($serializer->collection($activity));

        return $this->respondWithDocument($document);
    }
}
