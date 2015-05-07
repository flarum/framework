<?php namespace Flarum\Forum\Actions;

use Illuminate\Http\Request;
use Flarum\Forum\Events\UserLoggedIn;
use Flarum\Core\Repositories\UserRepositoryInterface;
use Flarum\Api\Request as ApiRequest;

class LoginAction extends BaseAction
{
    use MakesRememberCookie;

    protected $users;

    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    public function handle(Request $request, $routeParams = [])
    {
        $response = app('Flarum\Api\Actions\TokenAction')
            ->handle(new ApiRequest($request->only('identification', 'password')));

        if (($data = $response->getData()) && ! empty($data->token)) {
            $response->withCookie($this->makeRememberCookie($data->token));

            event(new UserLoggedIn($this->users->findOrFail($data->userId), $data->token));
        }

        return $response;
    }
}
