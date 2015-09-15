<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Actions;

use Flarum\Api\Client;
use Flarum\Api\AccessToken;
use Flarum\Events\UserLoggedIn;
use Flarum\Support\Action;
use Flarum\Api\Commands\GenerateAccessToken;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Illuminate\Contracts\Bus\Dispatcher;
use DateTime;

class RegisterAction extends Action
{
    use WritesRememberCookie;

    /**
     * @var Dispatcher
     */
    protected $bus;

    /**
     * @var Client
     */
    protected $apiClient;

    /**
     * @param Dispatcher $bus
     * @param Client $apiClient
     */
    public function __construct(Dispatcher $bus, Client $apiClient)
    {
        $this->bus = $bus;
        $this->apiClient = $apiClient;
    }

    /**
     * @param Request $request
     * @param array $routeParams
     *
     * @return JsonResponse
     */
    public function handle(Request $request, array $routeParams = [])
    {
        $params = ['data' => ['attributes' => $request->getAttributes()]];

        $apiResponse = $this->apiClient->send(app('flarum.actor'), 'Flarum\Api\Actions\Users\CreateAction', $params);

        $body = $apiResponse->getBody();
        $statusCode = $apiResponse->getStatusCode();

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
