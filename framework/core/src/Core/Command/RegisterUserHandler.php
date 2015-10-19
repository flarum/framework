<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Command;

use Flarum\Core\Access\AssertPermissionTrait;
use Flarum\Core\User;
use Flarum\Core\AuthToken;
use Flarum\Core\Validator\UserValidator;
use Flarum\Event\UserWillBeSaved;
use Flarum\Core\Support\DispatchEventsTrait;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Core\Exception\PermissionDeniedException;
use Illuminate\Contracts\Events\Dispatcher;

class RegisterUserHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var UserValidator
     */
    protected $validator;

    /**
     * @param Dispatcher $events
     * @param SettingsRepositoryInterface $settings
     * @param UserValidator $validator
     */
    public function __construct(Dispatcher $events, SettingsRepositoryInterface $settings, UserValidator $validator)
    {
        $this->events = $events;
        $this->settings = $settings;
        $this->validator = $validator;
    }

    /**
     * @param RegisterUser $command
     * @throws PermissionDeniedException if signup is closed and the actor is
     *     not an administrator.
     * @throws \Flarum\Core\Exception\InvalidConfirmationTokenException if an
     *     email confirmation token is provided but is invalid.
     * @return User
     */
    public function handle(RegisterUser $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        if (! $this->settings->get('allow_sign_up')) {
            $this->assertAdmin($actor);
        }

        $username = array_get($data, 'attributes.username');
        $email = array_get($data, 'attributes.email');
        $password = array_get($data, 'attributes.password');

        // If a valid authentication token was provided as an attribute,
        // then we won't require the user to choose a password.
        if (isset($data['attributes']['token'])) {
            $token = AuthToken::validOrFail($data['attributes']['token']);

            $password = $password ?: str_random(20);
        }

        $user = User::register($username, $email, $password);

        // If a valid authentication token was provided, then we will assign
        // the attributes associated with it to the user's account. If this
        // includes an email address, then we will activate the user's account
        // from the get-go.
        if (isset($token)) {
            foreach ($token->payload as $k => $v) {
                $user->$k = $v;
            }

            if (isset($token->payload['email'])) {
                $user->activate();
            }
        }

        $this->events->fire(
            new UserWillBeSaved($user, $actor, $data)
        );

        $this->validator->assertValid(array_merge($user->getAttributes(), compact('password')));

        $user->save();

        if (isset($token)) {
            $token->delete();
        }

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }
}
