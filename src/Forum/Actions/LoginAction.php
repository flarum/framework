<?php namespace Flarum\Forum\Actions;

use Flarum\Api\Client;
use Flarum\Forum\Events\UserLoggedIn;
use Flarum\Core\Repositories\UserRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class LoginAction extends BaseAction
{
    use WritesRememberCookie;

    protected $users;

    protected $apiClient;

    public function __construct(UserRepositoryInterface $users, Client $apiClient)
    {
        $this->users = $users;
        $this->apiClient = $apiClient;
    }

    public function handle(Request $request, $routeParams = [])
    {
        $params = array_only($request->getAttributes(), ['identification', 'password']);

        $data = $this->apiClient->send('Flarum\Api\Actions\TokenAction', $params);

        event(new UserLoggedIn($this->users->findOrFail($data->userId), $data->token));

        // TODO: The client needs to pass through exceptions
        return $this->withRememberCookie(
            $this->success(),
            $data->token
        );
    }
}
