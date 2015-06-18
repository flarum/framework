<?php namespace Flarum\Api\Actions\Activity;

use Flarum\Core\Repositories\UserRepositoryInterface;
use Flarum\Core\Repositories\ActivityRepositoryInterface;
use Flarum\Api\Actions\SerializeCollectionAction;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

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
     * @inheritdoc
     */
    public static $serializer = 'Flarum\Api\Serializers\ActivitySerializer';

    /**
     * @inheritdoc
     */
    public static $include = [
        'subject' => true,
        'subject.user' => true,
        'subject.discussion' => true
    ];

    /**
     * @inheritdoc
     */
    public static $link = ['user'];

    /**
     * @inheritdoc
     */
    public static $limitMax = 50;

    /**
     * @inheritdoc
     */
    public static $limit = 20;

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
     * @param \Tobscure\JsonApi\Document $document
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        $actor = $request->actor->getUser();

        $user = $this->users->findOrFail($request->get('users'), $actor);

        return $this->activity->findByUser($user->id, $actor, $request->limit, $request->offset, $request->get('type'))
            ->load($request->include);
    }
}
