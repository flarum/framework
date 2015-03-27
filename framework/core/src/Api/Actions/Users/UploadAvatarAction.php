<?php namespace Flarum\Api\Actions\Users;

use Flarum\Api\Actions\BaseAction;
use Flarum\Core\Commands\UploadAvatarCommand;
use Flarum\Api\Serializers\UserSerializer;
use Illuminate\Http\Request;

class UploadAvatarAction extends BaseAction
{
    public function handle(Request $request, $routeParams = [])
    {
        $userId = array_get($routeParams, 'id');
        $file = $request->file('avatar');

        $user = $this->dispatch(
            new UploadAvatarCommand($userId, $file, $this->actor->getUser()),
            $routeParams
        );

        $serializer = new UserSerializer;
        $document = $this->document()->setData($serializer->resource($user));

        return $this->respondWithDocument($document);
    }
}
