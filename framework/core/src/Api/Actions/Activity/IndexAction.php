<?php namespace Flarum\Api\Actions\Activity;

use Flarum\Core\Users\UserRepository;
use Flarum\Core\Activity\ActivityRepository;
use Flarum\Api\Actions\SerializeCollectionAction;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

class IndexAction extends SerializeCollectionAction
{
    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var ActivityRepository
     */
    protected $activity;

    /**
     * @inheritdoc
     */
    public $serializer = 'Flarum\Api\Serializers\ActivitySerializer';

    /**
     * @inheritdoc
     */
    public $include = [
        'subject' => true,
        'subject.user' => true,
        'subject.discussion' => true
    ];

    /**
     * @inheritdoc
     */
    public $link = ['user'];

    /**
     * @inheritdoc
     */
    public $limitMax = 50;

    /**
     * @inheritdoc
     */
    public $limit = 20;

    /**
     * @inheritdoc
     */
    public $sortFields = [];

    /**
     * @inheritdoc
     */
    public $sort;

    /**
     * @param UserRepository $users
     * @param ActivityRepository $activity
     */
    public function __construct(UserRepository $users, ActivityRepository $activity)
    {
        $this->users = $users;
        $this->activity = $activity;
    }

    /**
     * Get the activity results, ready to be serialized and assigned to the
     * document response.
     *
     * @param JsonApiRequest $request
     * @param Document $document
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        $userId = $request->get('filter.user');
        $actor = $request->actor;

        $user = $this->users->findOrFail($userId, $actor);

        return $this->activity->findByUser(
            $user->id,
            $actor,
            $request->limit,
            $request->offset,
            $request->get('filter.type')
        )
            ->load($request->include);
    }
}
