<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Api\Client;
use Flarum\Http\Session;
use Flarum\Event\UserLoggedIn;
use Flarum\Core\Repository\UserRepository;
use Flarum\Http\Controller\ControllerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class LogInController implements ControllerInterface
{
    /**
     * @var \Flarum\Core\Repository\UserRepository
     */
    protected $users;

    /**
     * @var Client
     */
    protected $apiClient;

    /**
     * @param \Flarum\Core\Repository\UserRepository $users
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
     * @return JsonResponse|EmptyResponse
     */
    public function handle(Request $request, array $routeParams = [])
    {
        $controller = 'Flarum\Api\Controller\TokenController';
        $session = $request->getAttribute('session');
        $params = array_only($request->getParsedBody(), ['identification', 'password']);

        $response = $this->apiClient->send($controller, $session, [], $params);

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody());

            $session = Session::find($data->token);
            $session->setDuration(60 * 24 * 14)->save();

            event(new UserLoggedIn($this->users->findOrFail($data->userId), $session));
        }

        return $response;
    }
}
