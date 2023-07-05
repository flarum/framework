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
use Flarum\Http\SlugManager;
use Flarum\User\User;
use Flarum\User\UserRepository;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class ShowUserController extends AbstractShowController
{
    public ?string $serializer = UserSerializer::class;

    public array $include = ['groups'];

    public function __construct(
        protected SlugManager $slugManager,
        protected UserRepository $users
    ) {
    }

    protected function data(ServerRequestInterface $request, Document $document): User
    {
        $id = Arr::get($request->getQueryParams(), 'id');
        $actor = RequestUtil::getActor($request);

        if (Arr::get($request->getQueryParams(), 'bySlug', false)) {
            $user = $this->slugManager->forResource(User::class)->fromSlug($id, $actor);
        } else {
            $user = $this->users->findOrFail($id, $actor);
        }

        if ($actor->id === $user->id) {
            $this->serializer = CurrentUserSerializer::class;
        }

        return $user;
    }
}
