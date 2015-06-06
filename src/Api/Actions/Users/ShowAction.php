<?php namespace Flarum\Api\Actions\Users;

use Flarum\Core\Repositories\UserRepositoryInterface;
use Flarum\Api\Actions\SerializeResourceAction;
use Flarum\Api\JsonApiRequest;
use Tobscure\JsonApi\Document;

class ShowAction extends SerializeResourceAction
{
    /**
     * @var \Flarum\Core\Repositories\UserRepositoryInterface
     */
    protected $users;

    /**
     * The name of the serializer class to output results with.
     *
     * @var string
     */
    public static $serializer = 'Flarum\Api\Serializers\CurrentUserSerializer';

    /**
     * The relationships that are available to be included, and which ones are
     * included by default.
     *
     * @var array
     */
    public static $include = [
        'groups' => true
    ];

    /**
     * Instantiate the action.
     *
     * @param \Flarum\Core\Repositories\UserRepositoryInterface $users
     */
    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    /**
     * Get a single user, ready to be serialized and assigned to the JsonApi
     * response.
     *
     * @param \Flarum\Api\JsonApiRequest $request
     * @param \Tobscure\JsonApi\Document $document
     * @return \Flarum\Core\Models\Discussion
     */
    protected function data(JsonApiRequest $request, Document $document)
    {
        $id = $request->get('id');

        if (! is_numeric($id)) {
            $id = $this->users->getIdForUsername($id);
        }

        return $this->users->findOrFail($id, $request->actor->getUser());
    }
}
