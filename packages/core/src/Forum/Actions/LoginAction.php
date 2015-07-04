<?php namespace Flarum\Forum\Actions;

use Flarum\Api\Client;
use Flarum\Forum\Events\UserLoggedIn;
use Flarum\Core\Users\UserRepository;
use Flarum\Support\Action;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class LoginAction extends Action
{
    use WritesRememberCookie;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @var Client
     */
    protected $apiClient;

    /**
     * @param UserRepository $users
     * @param Client $apiClient
     */
    public function __construct(UserRepository $users, Client $apiClient)
    {
        $this->users = $users;
        $this->apiClient = $apiClient;
    }

    /**
     * @param Request $request
     * @param array $routeParams
     * @return \Psr\Http\Message\ResponseInterface|EmptyResponse
     */
    public function handle(Request $request, array $routeParams = [])
    {
        $params = array_only($request->getAttributes(), ['identification', 'password']);

        $data = $this->apiClient->send(app('flarum.actor'), 'Flarum\Api\Actions\TokenAction', $params);

        // TODO: The client needs to pass through exceptions(?) or the whole
        // response so we can look at the response code. For now if there isn't
        // any useful data we just assume it's a 401.
        if (isset($data->userId)) {
            event(new UserLoggedIn($this->users->findOrFail($data->userId), $data->token));

            return $this->withRememberCookie(
                new JsonResponse($data),
                $data->token
            );
        } else {
            return new EmptyResponse(401);
        }
    }
}
