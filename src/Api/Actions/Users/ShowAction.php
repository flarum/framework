<?php namespace Flarum\Api\Actions\Users;

use Flarum\Core\Users\UserRepositoryInterface;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

class ShowAction extends SerializeResourceAction
{
    /**
     * @var UserRepositoryInterface
     */
    protected $users;

    /**
     * @inheritdoc
     */
    public static $serializer = 'Flarum\Api\Serializers\CurrentUserSerializer';

    /**
     * @inheritdoc
     */
    public static $include = [
        'groups' => true
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
     * @param UserRepositoryInterface $users
     */
    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    /**
     * Get a single user, ready to be serialized and assigned to the JsonApi
     * response.
     *
     * @param JsonApiRequest $request
     * @param Document $document
     * @return \Flarum\Core\Users\User
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        $id = $request->get('id');

        if (! is_numeric($id)) {
            $id = $this->users->getIdForUsername($id);
        }

        return $this->users->findOrFail($id, $request->actor);
    }
}
