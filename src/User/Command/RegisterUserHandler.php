<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\User\Command;

use Exception;
use Flarum\Foundation\DispatchEventsTrait;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\AssertPermissionTrait;
use Flarum\User\AuthToken;
use Flarum\User\AvatarUploader;
use Flarum\User\Event\Saving;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Flarum\User\UserValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;

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
     * @var AvatarUploader
     */
    protected $avatarUploader;

    /**
     * @var Factory
     */
    private $validatorFactory;

    /**
     * @param Dispatcher $events
     * @param SettingsRepositoryInterface $settings
     * @param UserValidator $validator
     * @param AvatarUploader $avatarUploader
     * @param Factory $validatorFactory
     */
    public function __construct(Dispatcher $events, SettingsRepositoryInterface $settings, UserValidator $validator, AvatarUploader $avatarUploader, Factory $validatorFactory)
    {
        $this->events = $events;
        $this->settings = $settings;
        $this->validator = $validator;
        $this->avatarUploader = $avatarUploader;
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * @param RegisterUser $command
     * @throws PermissionDeniedException if signup is closed and the actor is
     *     not an administrator.
     * @throws \Flarum\User\Exception\InvalidConfirmationTokenException if an
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

        if ($actor->isAdmin() && array_get($data, 'attributes.isEmailConfirmed')) {
            $user->activate();
        }

        $this->events->dispatch(
            new Saving($user, $actor, $data)
        );

        $this->validator->assertValid(array_merge($user->getAttributes(), compact('password')));

        if ($avatarUrl = array_get($data, 'attributes.avatarUrl')) {
            $validation = $this->validatorFactory->make(compact('avatarUrl'), ['avatarUrl' => 'url']);

            if ($validation->fails()) {
                throw new ValidationException($validation);
            }

            try {
                $image = (new ImageManager)->make($avatarUrl);

                $this->avatarUploader->upload($user, $image);
            } catch (Exception $e) {
                //
            }
        }

        $user->save();

        if (isset($token)) {
            $token->delete();
        }

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }
}
