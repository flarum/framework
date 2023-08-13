<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\Api\Serializer\UserSerializer;
use Flarum\Http\RequestUtil;
use Flarum\User\Command\EditUser;
use Flarum\User\Exception\NotAuthenticatedException;
use Flarum\User\User;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Tobscure\JsonApi\Document;

class UpdateUserController extends AbstractShowController
{
    public ?string $serializer = UserSerializer::class;

    public array $include = ['groups'];

    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function data(Request $request, Document $document): User
    {
        $id = $request->query('id');
        $actor = RequestUtil::getActor($request);
        $data = $request->json()->all();

        if ($actor->id == $id) {
            $this->serializer = CurrentUserSerializer::class;
        }

        // Require the user's current password if they are attempting to change
        // their own email address.
        if (isset($data['attributes']['email']) && $actor->id == $id) {
            $password = (string) Arr::get($data, 'meta.password');

            if (! $actor->checkPassword($password)) {
                throw new NotAuthenticatedException;
            }
        }

        return $this->bus->dispatch(
            new EditUser($id, $actor, $data)
        );
    }
}
