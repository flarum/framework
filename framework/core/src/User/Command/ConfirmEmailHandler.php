<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User\Command;

use Flarum\Foundation\DispatchEventsTrait;
use Flarum\User\EmailToken;
use Flarum\User\User;
use Flarum\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;

class ConfirmEmailHandler
{
    use DispatchEventsTrait;

    public function __construct(
        protected Dispatcher $events,
        protected UserRepository $users
    ) {
    }

    public function handle(ConfirmEmail $command): User
    {
        /** @var EmailToken $token */
        $token = EmailToken::validOrFail($command->token);

        $user = $token->user;
        $user->changeEmail($token->email);

        $user->activate();

        $user->save();
        $this->dispatchEventsFor($user);

        // Delete *all* tokens for the user, in case other ones were sent first
        $user->emailTokens()->delete();

        return $user;
    }
}
