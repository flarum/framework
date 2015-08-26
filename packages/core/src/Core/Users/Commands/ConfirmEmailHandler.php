<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Users\Commands;

use Flarum\Core\Users\UserRepository;
use Flarum\Events\UserWillBeSaved;
use Flarum\Core\Support\DispatchesEvents;
use Flarum\Core\Exceptions\InvalidConfirmationTokenException;
use Flarum\Core\Users\EmailToken;
use DateTime;

class ConfirmEmailHandler
{
    use DispatchesEvents;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param UserRepository $users
     */
    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * @param ConfirmEmail $command
     * @return \Flarum\Core\Users\User
     * @throws InvalidConfirmationTokenException
     */
    public function handle(ConfirmEmail $command)
    {
        $token = EmailToken::find($command->token);

        if (! $token || $token->created_at < new DateTime('-1 day')) {
            throw new InvalidConfirmationTokenException;
        }

        $user = $token->user;
        $user->changeEmail($token->email);

        if (! $user->is_activated) {
            $user->activate();
        }

        $user->save();
        $this->dispatchEventsFor($user);

        $token->delete();

        return $user;
    }
}
