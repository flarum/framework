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
use Flarum\User\AssertPermissionTrait;
use Flarum\User\AvatarUploader;
use Flarum\User\Event\GroupsChanged;
use Flarum\User\Event\Saving;
use Flarum\User\User;
use Flarum\User\UserRepository;
use Flarum\User\UserValidator;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;

class EditUserHandler
{
    use DispatchEventsTrait;
    use AssertPermissionTrait;

    /**
     * @var \Flarum\User\UserRepository
     */
    protected $users;

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
     * @param \Flarum\User\UserRepository $users
     * @param UserValidator $validator
     * @param AvatarUploader $avatarUploader
     * @param Factory $validatorFactory
     */
    public function __construct(Dispatcher $events, UserRepository $users, UserValidator $validator, AvatarUploader $avatarUploader, Factory $validatorFactory)
    {
        $this->events = $events;
        $this->users = $users;
        $this->validator = $validator;
        $this->avatarUploader = $avatarUploader;
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * @param EditUser $command
     * @return User
     * @throws \Flarum\User\Exception\PermissionDeniedException
     */
    public function handle(EditUser $command)
    {
        $actor = $command->actor;
        $data = $command->data;

        $user = $this->users->findOrFail($command->userId, $actor);

        $canEdit = $actor->can('edit', $user);
        $isSelf = $actor->id === $user->id;

        $attributes = array_get($data, 'attributes', []);
        $relationships = array_get($data, 'relationships', []);
        $validate = [];

        if (isset($attributes['username'])) {
            $this->assertPermission($canEdit);
            $user->rename($attributes['username']);
        }

        if (isset($attributes['email'])) {
            if ($isSelf) {
                $user->requestEmailChange($attributes['email']);

                if ($attributes['email'] !== $user->email) {
                    $validate['email'] = $attributes['email'];
                }
            } else {
                $this->assertPermission($canEdit);
                $user->changeEmail($attributes['email']);
            }
        }

        if ($actor->isAdmin() && ! empty($attributes['isActivated'])) {
            $user->activate();
        }

        if (isset($attributes['password'])) {
            $this->assertPermission($canEdit);
            $user->changePassword($attributes['password']);

            $validate['password'] = $attributes['password'];
        }

        if (! empty($attributes['readTime'])) {
            $this->assertPermission($isSelf);
            $user->markAllAsRead();
        }

        if (! empty($attributes['preferences'])) {
            $this->assertPermission($isSelf);

            foreach ($attributes['preferences'] as $k => $v) {
                $user->setPreference($k, $v);
            }
        }

        if (isset($relationships['groups']['data']) && is_array($relationships['groups']['data'])) {
            $this->assertPermission($canEdit);

            $newGroupIds = [];
            foreach ($relationships['groups']['data'] as $group) {
                if ($id = array_get($group, 'id')) {
                    $newGroupIds[] = $id;
                }
            }

            $user->raise(
                new GroupsChanged($user, $user->groups()->get()->all())
            );

            $user->afterSave(function (User $user) use ($newGroupIds) {
                $user->groups()->sync($newGroupIds);
            });
        }

        if ($avatarUrl = array_get($attributes, 'avatarUrl')) {
            $this->assertPermission($canEdit);

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
        } elseif (array_key_exists('avatarUrl', $attributes)) {
            $this->assertPermission($canEdit);

            $this->avatarUploader->remove($user);
        }

        $this->events->fire(
            new Saving($user, $actor, $data)
        );

        $this->validator->setUser($user);
        $this->validator->assertValid(array_merge($user->getDirty(), $validate));

        $user->save();

        $this->dispatchEventsFor($user, $actor);

        return $user;
    }
}
