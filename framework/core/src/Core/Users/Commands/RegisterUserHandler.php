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
use Flarum\Events\UserWillBeSaved;
use Flarum\Core\Support\DispatchesEvents;
use Flarum\Core\Settings\SettingsRepository;
use Flarum\Core\Exceptions\PermissionDeniedException;

class RegisterUserHandler
{
    use DispatchesEvents;

    protected $settings;

    public function __construct(SettingsRepository $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param RegisterUser $command
     * @return User
     * @throws PermissionDeniedException
     */
    public function handle(RegisterUser $command)
    {
        if (! $this->settings->get('allow_sign_up')) {
            throw new PermissionDeniedException;
        }

        $actor = $command->actor;
        $data = $command->data;

        $user = User::register(
            array_get($data, 'attributes.username'),
            array_get($data, 'attributes.email'),
            array_get($data, 'attributes.password')
        );

        event(new UserWillBeSaved($user, $actor, $data));

        $user->save();
        $this->dispatchEventsFor($user);

        return $user;
    }
}
