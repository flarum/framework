<?php

namespace Flarum\Flags\Api\Controller;

use Flarum\Api\Controller\AbstractDeleteController;
use Flarum\Flags\Event\UserFlagsDeleting;
use Flarum\Http\RequestUtil;
use Flarum\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface;

class DeleteUserFlagsController extends AbstractDeleteController
{
    public function __construct(protected UserRepository $users, protected Dispatcher $events)
    {
    }

    protected function delete(ServerRequestInterface $request): void
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertRegistered();
        $target = $this->users->findOrFail(Arr::get($request->getQueryParams(), 'id'), $actor);
        $actor->assertCan('viewFlags', $target);

        foreach ($target->flags as $flag) {
            $this->events->dispatch(new UserFlagsDeleting($flag, $actor, (array) $request->getParsedBody()));
        }

        $target->flags()->delete();
    }
}
