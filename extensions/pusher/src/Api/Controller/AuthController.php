<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Pusher\Api\Controller;

use Flarum\Http\Controller\AbstractController;
use Flarum\Http\RequestUtil;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Http\Request;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Pusher\Pusher;

class AuthController extends AbstractController
{
    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function __invoke(Request $request): ResponseInterface
    {
        $userChannel = 'private-user'.RequestUtil::getActor($request)->id;

        if ($request->input('channel_name') === $userChannel) {
            $pusher = new Pusher(
                $this->settings->get('flarum-pusher.app_key'),
                $this->settings->get('flarum-pusher.app_secret'),
                $this->settings->get('flarum-pusher.app_id'),
                ['cluster' => $this->settings->get('flarum-pusher.app_cluster')]
            );

            $payload = json_decode($pusher->socket_auth($userChannel, $request->input('socket_id')), true);

            return new JsonResponse($payload);
        }

        return new EmptyResponse(403);
    }
}
