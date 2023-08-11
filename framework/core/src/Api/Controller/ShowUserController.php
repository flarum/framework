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
use Illuminate\Http\Request;
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

    protected function data(Request $request, Document $document): User
    {
        $id = $request->query('id');
        $actor = RequestUtil::getActor($request);

        if ($request->query('bySlug', false)) {
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
