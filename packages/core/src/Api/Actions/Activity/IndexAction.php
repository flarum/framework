<?php namespace Flarum\Api\Actions\Activity;

use Flarum\Core\Repositories\UserRepositoryInterface;
use Flarum\Core\Repositories\ActivityRepositoryInterface;
use Flarum\Api\Actions\SerializeCollectionAction;
use Flarum\Api\JsonApiRequest;
use Flarum\Api\JsonApiResponse;

class IndexAction extends SerializeCollectionAction
{
    /**
     * @var \Flarum\Core\Repositories\UserRepositoryInterface
     */
    protected $users;

    /**
     * @var \Flarum\Core\Repositories\ActivityRepositoryInterface
     */
    protected $activity;

    /**
     * The name of the serializer class to output results with.
     *
     * @var string
     */
    public static $serializer = 'Flarum\Api\Serializers\ActivitySerializer';

    /**
     * The relationships that are available to be included, and which ones are
     * included by default.
     *
     * @var array
     */
    public static $include = [
        'subject' => true,
        'subject.user' => true,
        'subject.discussion' => true
    ];

    /**
     * The relations that are linked by default.
     *
     * @var array
     */
    public static $link = ['user'];

    /**
     * Instantiate the action.
     *
     * @param \Flarum\Core\Repositories\UserRepositoryInterface $users
     * @param \Flarum\Core\Repositories\ActivityRepositoryInterface $activity
     */
    public function __construct(UserRepositoryInterface $users, ActivityRepositoryInterface $activity)
    {
        $this->users = $users;
        $this->activity = $activity;
    }

    /**
     * Get the activity results, ready to be serialized and assigned to the
     * document response.
     *
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Flarum\Api\JsonApiResponse $response
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function data(JsonApiRequest $request, JsonApiResponse $response)
    {
        $actor = $request->actor->getUser();

        $user = $this->users->findOrFail($request->get('users'), $actor);

        return $this->activity->findByUser($user->id, $actor, $request->limit, $request->offset, $request->get('type'))
            ->load($request->include);
    }
}
