<?php namespace Flarum\Api\Actions\Users;

use Flarum\Api\Actions\BaseAction;
use Flarum\Core\Commands\UploadAvatarCommand;
use Illuminate\Http\Request;

class UploadAvatarAction extends BaseAction
{
    public function handle(Request $request, $routeParams = [])
    {
        $userId = array_get($routeParams, 'id');
        $file = $request->file('avatar');

        $this->dispatch(
            new UploadAvatarCommand($userId, $this->actor->getUser(), $file),
            $routeParams
        );

        return $this->respondWithoutContent(201);
    }
}
