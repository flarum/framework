<?php namespace Flarum\Forum\Actions;

use Flarum\Api\Client;
use Flarum\Forum\Events\UserLoggedIn;
use Flarum\Core\Repositories\UserRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\EmptyResponse;

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

        // TODO: The client needs to pass through exceptions(?) or the whole
        // response so we can look at the response code. For now if there isn't
        // any useful data we just assume it's a 401.
        if (isset($data->userId)) {
            event(new UserLoggedIn($this->users->findOrFail($data->userId), $data->token));

            $response = $this->success();
            $response->getBody()->write(json_encode($data));

            return $this->withRememberCookie(
                $response,
                $data->token
            );
        } else {
            return new EmptyResponse(401);
        }
    }
}
