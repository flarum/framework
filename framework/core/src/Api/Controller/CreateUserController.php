<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\Serializer\CurrentUserSerializer;
use Flarum\Http\RequestUtil;
use Flarum\User\Command\RegisterUser;
use Flarum\User\User;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Tobscure\JsonApi\Document;

class CreateUserController extends AbstractCreateController
{
    public ?string $serializer = CurrentUserSerializer::class;

    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    protected function data(Request $request, Document $document): User
    {
        return $this->bus->dispatch(
            new RegisterUser(RequestUtil::getActor($request), $request->json('data', []))
        );
    }
}
