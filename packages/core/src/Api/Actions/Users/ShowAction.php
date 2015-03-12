<?php namespace Flarum\Api\Actions\Users;

use Flarum\Core\Repositories\UserRepositoryInterface;
use Flarum\Core\Support\Actor;
use Flarum\Api\Actions\ApiParams;
use Flarum\Api\Actions\BaseAction;
use Flarum\Api\Serializers\UserSerializer;

class ShowAction extends BaseAction
{
    protected $actor;

    protected $users;

    public function __construct(Actor $actor, UserRepositoryInterface $users)
    {
        $this->actor = $actor;
        $this->users = $users;
    }

    /**
     * Show a single user.
     *
     * @return Response
     */
    public function run(ApiParams $params)
    {
        $id = $params->get('id');

        if (! is_numeric($id)) {
            $id = $this->users->getIdForUsername($id);
        }

        $user = $this->users->findOrFail($id, $this->actor->getUser());

        // Set up the user serializer, which we will use to create the
        // document's primary resource. We will specify that we want the
        // 'groups' relation to be included by default.
        $serializer = new UserSerializer(['groups']);
        $document = $this->document()->setPrimaryElement($serializer->resource($user));

        return $this->respondWithDocument($document);
    }
}
