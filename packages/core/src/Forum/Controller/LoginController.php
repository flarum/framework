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
use Flarum\Api\AccessToken;
use Flarum\Event\UserLoggedIn;
use Flarum\Core\Repository\UserRepository;
use Flarum\Http\Controller\ControllerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use DateTime;

class LoginController implements ControllerInterface
{
    use WriteRememberCookieTrait;

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
        $actor = $request->getAttribute('actor');
        $params = array_only($request->getParsedBody(), ['identification', 'password']);

        $response = $this->apiClient->send($controller, $actor, [], $params);

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody());

            // Extend the token's expiry to 2 weeks so that we can set a
            // remember cookie
            AccessToken::where('id', $data->token)->update(['expires_at' => new DateTime('+2 weeks')]);

            event(new UserLoggedIn($this->users->findOrFail($data->userId), $data->token));

            return $this->withRememberCookie(
                $response,
                $data->token
            );
        } else {
            return $response;
        }
    }
}
