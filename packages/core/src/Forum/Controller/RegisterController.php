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
use Flarum\Http\Controller\ControllerInterface;
use Flarum\Api\Command\GenerateAccessToken;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Illuminate\Contracts\Bus\Dispatcher;
use DateTime;

class RegisterController implements ControllerInterface
{
    use WriteRememberCookieTrait;

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

        $response = new JsonResponse($body, $statusCode);

        if (! empty($body->data->attributes->isActivated)) {
            $token = $this->bus->dispatch(new GenerateAccessToken($body->data->id));

            // Extend the token's expiry to 2 weeks so that we can set a
            // remember cookie
            AccessToken::where('id', $token->id)->update(['expires_at' => new DateTime('+2 weeks')]);

            return $this->withRememberCookie(
                $response,
                $token->id
            );
        }

        return $response;
    }
}
