<?php namespace Flarum\Forum\Actions;

use Flarum\Forum\Events\UserLoggedIn;
use Flarum\Core\Repositories\UserRepositoryInterface;
use Flarum\Api\Request as ApiRequest;
use Psr\Http\Message\ServerRequestInterface as Request;

class LoginAction extends BaseAction
{
    use WritesRememberCookie;

    protected $users;

    public function __construct(UserRepositoryInterface $users)
    {
        $this->users = $users;
    }

    public function handle(Request $request, $routeParams = [])
    {
        $params = array_only($request->getAttributes(), ['identification', 'password']);

        /** @var \Psr\Http\Message\ResponseInterface $response */
        $response = app('Flarum\Api\Actions\TokenAction')->handle(new ApiRequest($params));

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody());

            event(new UserLoggedIn($this->users->findOrFail($data->userId), $data->token));
            return $this->withRememberCookie($response, $data->token);
        }

        return $response;
    }
}
