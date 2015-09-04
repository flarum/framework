<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Pusher\Api;

use Flarum\Api\Actions\JsonApiAction;
use Flarum\Api\Request;
use Flarum\Core\Settings\SettingsRepository;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\EmptyResponse;
use Pusher;

class AuthAction extends JsonApiAction
{
    protected $settings;

    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    protected function respond(Request $request)
    {
        $userChannel = 'private-user' . $request->actor->id;

        if ($request->get('channel_name') === $userChannel) {
            $pusher = new Pusher(
                $this->settings->get('pusher.app_key'),
                $this->settings->get('pusher.app_secret'),
                $this->settings->get('pusher.app_id')
            );

            $payload = json_decode($pusher->socket_auth($userChannel, $request->get('socket_id')), true);

            return new JsonResponse($payload);
        }

        return new EmptyResponse(403);
    }
}
