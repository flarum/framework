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
use Flarum\Core\User;
use Flarum\Http\Controller\ControllerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Illuminate\Contracts\Bus\Dispatcher;

class RegisterController implements ControllerInterface
{
    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @var Client
     */
    protected $api;

    /**
     * @param Dispatcher $bus
     * @param Client $api
     */
    public function __construct(Dispatcher $bus, Client $api)
    {
        $this->bus = $bus;
        $this->api = $api;
    }

    /**
     * @param Request $request
     * @param array $routeParams
     *
     * @return JsonResponse
     */
    public function handle(Request $request, array $routeParams = [])
    {
        $controller = 'Flarum\Api\Controller\CreateUserController';
        $actor = $request->getAttribute('actor');
        $body = ['data' => ['attributes' => $request->getParsedBody()]];

        $response = $this->api->send($controller, $actor, [], $body);

        $body = json_decode($response->getBody());
        $statusCode = $response->getStatusCode();

        if (isset($body->data)) {
            $user = User::find($body->data->id);

            $session = $request->getAttribute('session');
            $session->assign($user)->regenerateId()->renew()->setDuration(60 * 24 * 14)->save();
        }

        return new JsonResponse($body, $statusCode);
    }
}
