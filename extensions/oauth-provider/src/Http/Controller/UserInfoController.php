<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Http\Controller;

use Flarum\OAuthProvider\Server\AuthorizationServerFactory;
use Flarum\User\UserRepository;
use Laminas\Diactoros\Response\JsonResponse;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class UserInfoController implements RequestHandlerInterface
{
    public function __construct(
        protected AuthorizationServerFactory $factory,
        protected UserRepository $users,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $resourceServer = $this->factory->resourceServer();

        try {
            $request = $resourceServer->validateAuthenticatedRequest($request);
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse(new \Laminas\Diactoros\Response());
        }

        $userId = (int) $request->getAttribute('oauth_user_id');
        $scopes = (array) $request->getAttribute('oauth_scopes', []);

        $user = $this->users->findOrFail($userId);

        $payload = [
            'sub' => (string) $user->id,
        ];

        if (in_array('profile', $scopes, true)) {
            $payload['name'] = $user->display_name;
            $payload['picture'] = $user->avatar_url;

            // Non-standard extension: srcset string for HiDPI variants when the
            // avatar driver provides them (e.g. uploaded avatars expose @2x/@3x).
            // Format: "url 1x, url@2x 2x, url@3x 3x" — same as <img srcset>.
            $srcset = $user->avatar_srcset;
            if (! empty($srcset)) {
                $payload['picture_srcset'] = $srcset;
            }
        }

        if (in_array('email', $scopes, true)) {
            $payload['email'] = $user->email;
            $payload['email_verified'] = (bool) $user->is_email_confirmed;
        }

        return new JsonResponse($payload);
    }
}
