<?php namespace Flarum\Forum\Actions;

use Illuminate\Http\Request;
use Flarum\Forum\Events\UserLoggedIn;
use Flarum\Core\Repositories\UserRepositoryInterface;

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
        $response = $this->callAction('Flarum\Api\Actions\TokenAction', $request->only('identification', 'password'));

        $data = $response->getData();
        if (! empty($data->token)) {
            $response->withCookie($this->makeRememberCookie($data->token));

            event(new UserLoggedIn($this->users->findOrFail($data->userId), $data->token));
        }

        return $response;
    }
}
