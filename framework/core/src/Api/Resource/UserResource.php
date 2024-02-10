<?php

namespace Flarum\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Schema;
use Flarum\Http\SlugManager;
use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Event\Deleting;
use Flarum\User\Event\GroupsChanged;
use Flarum\User\Event\RegisteringFromProvider;
use Flarum\User\Event\Saving;
use Flarum\User\RegistrationToken;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tobyz\JsonApiServer\Laravel\Sort\SortColumn;

class UserResource extends AbstractDatabaseResource
{
    public function type(): string
    {
        return 'users';
    }

    public function model(): string
    {
        return User::class;
    }

    public function scope(Builder $query, \Tobyz\JsonApiServer\Context $context): void
    {
        $query->whereVisibleTo($context->getActor());
    }

    public function find(string $id, \Tobyz\JsonApiServer\Context $context): ?object
    {
        $slugManager = resolve(SlugManager::class);
        $actor = $context->getActor();

        if (Arr::get($context->request->getQueryParams(), 'bySlug', false)) {
            $user = $slugManager->forResource(User::class)->fromSlug($id, $actor);
        } else {
            $user = $this->query($context)->findOrFail($id);
        }

        return $user;
    }

    public function endpoints(): array
    {
        return [
            Endpoint\Create::make()
                ->visible(function (Context $context) {
                    $settings = resolve(SettingsRepositoryInterface::class);

                    if (! $settings->get('allow_sign_up')) {
                        return $context->getActor()->isAdmin();
                    }

                    return true;
                }),
            Endpoint\Update::make()
                ->authenticated()
                ->defaultInclude(['groups']),
            Endpoint\Delete::make()
                ->authenticated()
                ->can('delete'),
            Endpoint\Show::make()
                ->defaultInclude(['groups']),
            Endpoint\Index::make()
                ->can('searchUsers')
                ->defaultInclude(['groups'])
                ->paginate(),
        ];
    }

    public function fields(): array
    {
        return [
            Schema\Str::make('username')
                ->requiredOnCreate()
                ->unique('users', 'username', true)
                ->regex('/^[a-z0-9_-]+$/i')
                ->validationMessages([
                    'username.regex' => resolve(TranslatorInterface::class)->trans('core.api.invalid_username_message')
                ])
                ->minLength(3)
                ->maxLength(30)
                ->writable(function (User $user, Context $context) {
                    return $context->endpoint instanceof Endpoint\Create
                        || $context->getActor()->can('editCredentials', $user);
                })
                ->set(function (User $user, string $value) {
                    if ($user->exists) {
                        $user->rename($value);
                    } else {
                        $user->username = $value;
                    }
                }),
            Schema\Str::make('email')
                ->requiredOnCreate()
                ->email(['filter'])
                ->unique('users', 'email', true)
                ->visible(function (User $user, Context $context) {
                    return $context->getActor()->can('editCredentials', $user)
                        || $context->getActor()->id === $user->id;
                })
                ->writable(function (User $user, Context $context) {
                    return $context->endpoint instanceof Endpoint\Create
                        || $context->getActor()->can('editCredentials', $user)
                        || $context->getActor()->id === $user->id;
                })
                ->set(function (User $user, string $value, Context $context) {
                    if ($user->exists) {
                        $isSelf = $context->getActor()->id === $user->id;

                        if ($isSelf) {
                            $user->requestEmailChange($value);
                        } else {
                            $context->getActor()->assertCan('editCredentials', $user);
                            $user->changeEmail($value);
                        }
                    } else {
                        $user->email = $value;
                    }
                }),
            Schema\Boolean::make('isEmailConfirmed')
                ->visible(function (User $user, Context $context) {
                    return $context->getActor()->can('editCredentials', $user)
                        || $context->getActor()->id === $user->id;
                })
                ->writable(fn (User $user, Context $context) => $context->getActor()->isAdmin())
                ->set(function (User $user, $value, Context $context) {
                    $editing = $context->endpoint instanceof Endpoint\Update;

                    if (! empty($value) && ($editing || $context->getActor()->isAdmin())) {
                        $user->activate();
                    }
                }),
            Schema\Str::make('password')
                ->requiredOnCreateWithout(['token'])
                ->minLength(8)
                ->visible(false)
                ->writable(function (User $user, Context $context) {
                    return $context->endpoint instanceof Endpoint\Create
                        || $context->getActor()->can('editCredentials', $user);
                })
                ->set(function (User $user, ?string $value) {
                    $user->exists && $user->changePassword($value);
                }),
            // Registration token.
            Schema\Str::make('token')
                ->visible(false)
                ->writable(function (User $user, Context $context) {
                    return $context->endpoint instanceof Endpoint\Create;
                })
                ->set(function (User $user, ?string $value) {
                    if ($value) {
                        $token = RegistrationToken::validOrFail($value);

                        $user->setAttribute('token', $token);
                        $user->password ??= Str::random(20);

                        $this->applyToken($user, $token);
                    }
                }),
            Schema\Str::make('displayName'),
            Schema\Str::make('avatarUrl'),
            Schema\Str::make('slug')
                ->get(function (User $user) {
                    return resolve(SlugManager::class)->forResource(User::class)->toSlug($user);
                }),
            Schema\DateTime::make('joinTime')
                ->property('joined_at'),
            Schema\Integer::make('discussionCount'),
            Schema\Integer::make('commentCount'),
            Schema\DateTime::make('lastSeenAt')
                ->visible(function (User $user, Context $context) {
                    return $user->getPreference('discloseOnline') || $context->getActor()->can('viewLastSeenAt', $user);
                }),

            Schema\DateTime::make('markedAllAsReadAt')
                ->visible(fn (User $user, Context $context) => $context->getActor()->id === $user->id)
                ->writable(fn (User $user, Context $context) => $context->getActor()->id === $user->id)
                ->set(function (User $user, $value) {
                    if (! empty($value)) {
                        $user->markAllAsRead();
                    }
                }),

            Schema\Integer::make('unreadNotificationCount')
                ->visible(fn (User $user, Context $context) => $context->getActor()->id === $user->id)
                ->get(function (User $user): int {
                    return $user->getUnreadNotificationCount();
                }),
            Schema\Integer::make('newNotificationCount')
                ->visible(fn (User $user, Context $context) => $context->getActor()->id === $user->id)
                ->get(function (User $user): int {
                    return $user->getNewNotificationCount();
                }),
            Schema\Arr::make('preferences')
                ->visible(fn (User $user, Context $context) => $context->getActor()->id === $user->id)
                ->writable(fn (User $user, Context $context) => $context->getActor()->id === $user->id)
                ->set(function (User $user, array $value) {
                    foreach ($value as $k => $v) {
                        $user->setPreference($k, $v);
                    }
                }),

            Schema\Boolean::make('isAdmin')
                ->visible(fn (User $user, Context $context) => $context->getActor()->id === $user->id)
                ->get(fn (User $user, Context $context) => $context->getActor()->isAdmin()),

            Schema\Boolean::make('canEdit')
                ->get(function (User $user, Context $context) {
                    return $context->getActor()->can('edit', $user);
                }),
            Schema\Boolean::make('canEditCredentials')
                ->get(function (User $user, Context $context) {
                    return $context->getActor()->can('editCredentials', $user);
                }),
            Schema\Boolean::make('canEditGroups')
                ->get(function (User $user, Context $context) {
                    return $context->getActor()->can('editGroups', $user);
                }),
            Schema\Boolean::make('canDelete')
                ->get(function (User $user, Context $context) {
                    return $context->getActor()->can('delete', $user);
                }),

            Schema\Relationship\ToMany::make('groups')
                ->writable(fn (User $user, Context $context) => $context->endpoint instanceof Endpoint\Update && $context->getActor()->can('editGroups', $user))
                ->includable()
                ->get(function (User $user, Context $context) {
                    if ($context->getActor()->can('viewHiddenGroups')) {
                        return $user->groups->all();
                    }

                    return $user->visibleGroups->all();
                })
                ->set(function (User $user, $value, Context $context) {
                    $actor = $context->getActor();

                    $oldGroups = $user->groups()->get()->all();
                    $oldGroupIds = Arr::pluck($oldGroups, 'id');

                    $newGroupIds = [];
                    foreach ($value as $group) {
                        if ($id = Arr::get($group, 'id')) {
                            $newGroupIds[] = $id;
                        }
                    }

                    // Ensure non-admins aren't adding/removing admins
                    $adminChanged = in_array('1', array_diff($oldGroupIds, $newGroupIds)) || in_array('1', array_diff($newGroupIds, $oldGroupIds));
                    $actor->assertPermission(! $adminChanged || $actor->isAdmin());

                    $user->raise(
                        new GroupsChanged($user, $oldGroups)
                    );

                    $user->afterSave(function (User $user) use ($newGroupIds) {
                        $user->groups()->sync($newGroupIds);
                        $user->unsetRelation('groups');
                    });
                }),
        ];
    }

    public function sorts(): array
    {
        return [
            SortColumn::make('username'),
            SortColumn::make('commentCount'),
            SortColumn::make('discussionCount'),
            SortColumn::make('lastSeenAt')
                ->visible(function (Context $context) {
                    return $context->getActor()->hasPermission('user.viewLastSeenAt');
                }),
            SortColumn::make('joinedAt'),
        ];
    }

    /** @param User $model */
    public function saved(object $model, \Tobyz\JsonApiServer\Context $context): ?object
    {
        if (($token = $model->getAttribute('token')) instanceof RegistrationToken) {
            $this->fulfillToken($model, $token);
        }

        return parent::saved($model, $context);
    }

    public function deleting(object $model, \Tobyz\JsonApiServer\Context $context): void
    {
        $this->events->dispatch(
            new Deleting($model, $context->getActor(), [])
        );
    }

    protected function bcSavingEvent(\Tobyz\JsonApiServer\Context $context, array $data): ?object
    {
        return new Saving($context->model, $context->getActor(), $data);
    }

    private function applyToken(User $user, RegistrationToken $token): void
    {
        foreach ($token->user_attributes as $k => $v) {
            if ($k === 'avatar_url') {
                $this->uploadAvatarFromUrl($user, $v);
                continue;
            }

            $user->$k = $v;

            if ($k === 'email') {
                $user->activate();
            }
        }

        $this->events->dispatch(
            new RegisteringFromProvider($user, $token->provider, $token->payload)
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    private function uploadAvatarFromUrl(User $user, string $url): void
    {
        // @todo: constructor dependency injection
        $this->validator = resolve(\Illuminate\Contracts\Validation\Factory::class);
        $this->imageManager = resolve(\Intervention\Image\ImageManager::class);
        $this->avatarUploader = resolve(\Flarum\User\AvatarUploader::class);

        $urlValidator = $this->validator->make(compact('url'), [
            'url' => 'required|active_url',
        ]);

        if ($urlValidator->fails()) {
            throw new InvalidArgumentException('Provided avatar URL must be a valid URI.', 503);
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! in_array($scheme, ['http', 'https'])) {
            throw new InvalidArgumentException("Provided avatar URL must have scheme http or https. Scheme provided was $scheme.", 503);
        }

        $image = $this->imageManager->make($url);

        $this->avatarUploader->upload($user, $image);
    }

    private function fulfillToken(User $user, RegistrationToken $token): void
    {
        $token->delete();

        if ($token->provider && $token->identifier) {
            $user->loginProviders()->create([
                'provider' => $token->provider,
                'identifier' => $token->identifier
            ]);
        }
    }
}
