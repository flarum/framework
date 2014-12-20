<?php namespace Flarum\Api\Actions\Users;

use Flarum\Core\Users\User;
use Flarum\Api\Actions\Base;
use Flarum\Api\Serializers\UserSerializer;

class Show extends Base
{
    /**
     * Show a single user.
     *
     * @return Response
     */
    protected function run()
    {
        $user = User::whereCanView()->findOrFail($this->param('id'));

        // Set up the user serializer, which we will use to create the
        // document's primary resource. We will specify that we want the
        // 'groups' relation to be included by default.
        $serializer = new UserSerializer(['groups']);
        $this->document->setPrimaryElement($serializer->resource($user));

        return $this->respondWithDocument();
    }
}
