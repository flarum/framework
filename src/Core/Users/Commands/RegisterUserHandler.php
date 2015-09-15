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

use Flarum\Core\Users\User;
use Flarum\Core\Users\EmailToken;
use Flarum\Events\UserWillBeSaved;
use Flarum\Core\Support\DispatchesEvents;
use Flarum\Core\Settings\SettingsRepository;
use Flarum\Core\Exceptions\PermissionDeniedException;
use DateTime;

class RegisterUserHandler
{
    use DispatchesEvents;

    /**
     * @var SettingsRepository
     */
    protected $settings;

    /**
     * @param SettingsRepository $settings
     */
    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param RegisterUser $command
     *
     * @throws PermissionDeniedException if signup is closed and the actor is
     *     not an administrator.
     * @throws \Flarum\Core\Exceptions\InvalidConfirmationTokenException if an
     *     email confirmation token is provided but is invalid.
     *
     * @return User
     */
    public function handle(RegisterUser $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        if (! $this->settings->get('allow_sign_up') && ! $actor->isAdmin()) {
            throw new PermissionDeniedException;
        }

        // If a valid email confirmation token was provided as an attribute,
        // then we can create a random password for this user and consider their
        // email address confirmed.
        if (isset($data['attributes']['token'])) {
            $token = EmailToken::whereNull('user_id')->validOrFail($data['attributes']['token']);

            $email = $token->email;
            $password = array_get($data, 'attributes.password', str_random(20));
        } else {
            $email = array_get($data, 'attributes.email');
            $password = array_get($data, 'attributes.password');
        }

        // Create the user's new account. If their email was set via token, then
        // we can activate their account from the get-go, and they won't need
        // to confirm their email address.
        $user = User::register(
            array_get($data, 'attributes.username'),
            $email,
            $password
        );

        if (isset($token)) {
            $user->activate();
        }

        event(new UserWillBeSaved($user, $actor, $data));

        $user->save();

        if (isset($token)) {
            $token->delete();
        }

        $this->dispatchEventsFor($user);

        return $user;
    }
}
