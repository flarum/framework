<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Resource;

use Flarum\Api\Context;
use Flarum\Api\Endpoint;
use Flarum\Api\Schema;
use Flarum\Api\Sort\SortColumn;
use Flarum\Bus\Dispatcher;
use Flarum\Foundation\ValidationException;
use Flarum\Http\SlugManager;
use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\AvatarUploader;
use Flarum\User\AvatarValidator;
use Flarum\User\Command\DeleteAvatar;
use Flarum\User\Command\UploadAvatar;
use Flarum\User\Event\Deleting;
use Flarum\User\Event\GroupsChanged;
use Flarum\User\Event\RegisteringFromProvider;
use Flarum\User\Event\Saving;
use Flarum\User\Exception\NotAuthenticatedException;
use Flarum\User\RegistrationToken;
use Flarum\User\User;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use InvalidArgumentException;

/**
 * @extends AbstractDatabaseResource<User>
 */
class UserResource extends AbstractDatabaseResource
{
    public function __construct(
        protected TranslatorInterface $translator,
        protected SlugManager $slugManager,
        protected SettingsRepositoryInterface $settings,
        protected ImageManager $imageManager,
        protected AvatarUploader $avatarUploader,
        protected AvatarValidator $imageValidator,
        protected Dispatcher $bus,
    ) {
    }

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
        $actor = $context->getActor();

        if (Arr::get($context->request->getQueryParams(), 'bySlug', false)) {
            $user = $this->slugManager->forResource(User::class)->fromSlug($id, $actor);
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
                    if (! $this->settings->get('allow_sign_up')) {
                        return $context->getActor()->isAdmin();
                    }

                    return true;
                }),
            Endpoint\Update::make()
                ->visible(function (User $user, Context $context) {
                    $actor = $context->getActor();
                    $body = $context->body();

                    // Require the user's current password if they are attempting to change
                    // their own email address.

                    if (isset($body['data']['attributes']['email']) && $actor->id === $user->id) {
                        $password = (string) Arr::get($body, 'meta.password');

                        if (! $actor->checkPassword($password)) {
                            throw new NotAuthenticatedException;
                        }
                    }

                    $actor->assertRegistered();

                    return true;
                })
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
            Endpoint\Endpoint::make('avatar.upload')
                ->route('POST', '/{id}/avatar')
                ->action(function (Context $context) {
                    $file = Arr::get($context->request->getUploadedFiles(), 'avatar');

                    return $this->bus->dispatch(
                        new UploadAvatar((int) $context->modelId, $file, $context->getActor())
                    );
                }),
            Endpoint\Endpoint::make('avatar.delete')
                ->route('DELETE', '/{id}/avatar')
                ->action(function (Context $context) {
                    return $this->bus->dispatch(
                        new DeleteAvatar(Arr::get($context->request->getQueryParams(), 'id'), $context->getActor())
                    );
                }),
        ];
    }

    public function fields(): array
    {
        $translator = $this->translator;

        return [
            Schema\Str::make('username')
                ->requiredOnCreateWithout(['token'])
                ->unique('users', 'username', true)
                ->regex('/^(?![0-9]*$)[a-z0-9_-]+$/i')
                ->validationMessages([
                    'username.regex' => $translator->trans('core.api.invalid_username_message'),
                    'username.required_without' => $translator->trans('validation.required', ['attribute' => $translator->trans('validation.attributes.username')])
                ])
                ->minLength(3)
                ->maxLength(30)
                ->writable(function (User $user, Context $context) {
                    return $context->creating()
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
                ->requiredOnCreateWithout(['token'])
                ->validationMessages([
                    'email.required_without' => $translator->trans('validation.required', ['attribute' => $translator->trans('validation.attributes.email')])
                ])
                ->email(['filter'])
                ->unique('users', 'email', true)
                ->visible(function (User $user, Context $context) {
                    return $context->getActor()->can('editCredentials', $user)
                        || $context->getActor()->id === $user->id;
                })
                ->writable(function (User $user, Context $context) {
                    return $context->creating()
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
                    if (! empty($value) && ($context->updating() || $context->getActor()->isAdmin())) {
                        $user->activate();
                    }
                }),
            Schema\Str::make('password')
                ->requiredOnCreateWithout(['token'])
                ->validationMessages([
                    'password.required_without' => $translator->trans('validation.required', ['attribute' => $translator->trans('validation.attributes.password')])
                ])
                ->minLength(8)
                ->visible(false)
                ->writable(function (User $user, Context $context) {
                    return $context->creating()
                        || $context->getActor()->can('editCredentials', $user);
                })
                ->set(function (User $user, ?string $value) {
                    if ($user->exists) {
                        $user->changePassword($value);
                    } else {
                        $user->password = $value;
                    }
                }),
            // Registration token.
            Schema\Str::make('token')
                ->visible(false)
                ->writable(function (User $user, Context $context) {
                    return $context->creating();
                })
                ->set(function (User $user, ?string $value, Context $context) {
                    if ($value) {
                        /** @var RegistrationToken $token */
                        $token = RegistrationToken::validOrFail($value);

                        $context->setParam('token', $token);
                        $user->password ??= Str::random(20);

                        $this->applyToken($user, $token);
                    }
                })
                ->save(fn () => null),
            Schema\Str::make('displayName'),
            Schema\Str::make('avatarUrl'),
            Schema\Str::make('avatarSrcset'),
            Schema\Boolean::make('hasUploadedAvatar'),
            Schema\Str::make('slug')
                ->get(function (User $user) {
                    return $this->slugManager->forResource(User::class)->toSlug($user);
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
                ->visible(fn (User $user, Context $context) => ($context->collection instanceof self || $context->collection instanceof ForumResource) && $context->getActor()->id === $user->id)
                ->writable(fn (User $user, Context $context) => $context->getActor()->id === $user->id)
                ->set(function (User $user, $value) {
                    if (! empty($value)) {
                        $user->markAllAsRead();
                    }
                }),

            Schema\Integer::make('unreadNotificationCount')
                ->visible(fn (User $user, Context $context) => ($context->collection instanceof self || $context->collection instanceof ForumResource) && $context->getActor()->id === $user->id)
                ->get(function (User $user): int {
                    return $user->getUnreadNotificationCount();
                }),
            Schema\Integer::make('newNotificationCount')
                ->visible(fn (User $user, Context $context) => ($context->collection instanceof self || $context->collection instanceof ForumResource) && $context->getActor()->id === $user->id)
                ->get(function (User $user): int {
                    return $user->getNewNotificationCount();
                }),
            Schema\Arr::make('preferences')
                ->visible(fn (User $user, Context $context) => ($context->collection instanceof self || $context->collection instanceof ForumResource) && $context->getActor()->id === $user->id)
                ->writable(fn (User $user, Context $context) => $context->getActor()->id === $user->id)
                ->set(function (User $user, array $value) {
                    foreach ($value as $k => $v) {
                        $user->setPreference($k, $v);
                    }
                }),

            Schema\Boolean::make('isAdmin')
                ->visible(fn (User $user, Context $context) => ($context->collection instanceof self || $context->collection instanceof ForumResource) && $context->getActor()->id === $user->id)
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
                ->writable(fn (User $user, Context $context) => $context->updating() && $context->getActor()->can('editGroups', $user))
                ->includable()
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
        if (($token = $context->getParam('token')) instanceof RegistrationToken) {
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

    public function saving(object $model, \Tobyz\JsonApiServer\Context $context): ?object
    {
        $this->events->dispatch(
            new Saving($model, $context->getActor(), Arr::get($context->body(), 'data', []))
        );

        return $model;
    }

    private function applyToken(User $user, #[\SensitiveParameter] RegistrationToken $token): void
    {
        $attributes = $token->user_attributes;

        foreach ($attributes as $k => $v) {
            if ($k === 'avatar_url') {
                $url2x = $attributes['avatar_url_2x'] ?? null;
                $url3x = $attributes['avatar_url_3x'] ?? null;
                $this->uploadAvatarFromUrl($user, $v, $url2x, $url3x);
                continue;
            }

            // These are handled above alongside avatar_url.
            if ($k === 'avatar_url_2x' || $k === 'avatar_url_3x') {
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
    private function uploadAvatarFromUrl(User $user, string $url, ?string $url2x = null, ?string $url3x = null): void
    {
        $this->assertValidAvatarUrl($url);

        $urlContents = $this->retrieveAvatarFromUrl($url);

        if ($urlContents === null) {
            return;
        }

        // If the OAuth driver provided explicit HiDPI URLs, fetch and store them directly.
        if ($url2x !== null || $url3x !== null) {
            $image1x = $this->imageManager->read($urlContents);
            $image2x = $url2x !== null ? $this->readAvatarFromUrl($url2x) : null;
            $image3x = $url3x !== null ? $this->readAvatarFromUrl($url3x) : null;

            $this->avatarUploader->uploadPresized($user, $image1x, $image2x, $image3x);
        } else {
            $image = $this->imageManager->read($urlContents);

            $this->avatarUploader->upload($user, $image);
        }
    }

    /**
     * @throws ValidationException
     */
    private function assertValidAvatarUrl(string $url): void
    {
        $urlValidator = $this->validation->make(compact('url'), [
            'url' => 'required|active_url',
        ]);

        if ($urlValidator->fails()) {
            throw new ValidationException([
                'avatar_url' => 'Provided avatar URL must be a valid URI.',
            ]);
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! in_array($scheme, ['http', 'https'])) {
            throw new ValidationException([
                'avatar_url' => "Provided avatar URL must have scheme http or https. Scheme provided was $scheme.",
            ]);
        }
    }

    private function readAvatarFromUrl(string $url): ?\Intervention\Image\Interfaces\ImageInterface
    {
        $contents = $this->retrieveAvatarFromUrl($url);

        return $contents !== null ? $this->imageManager->read($contents) : null;
    }

    private function retrieveAvatarFromUrl(string $url): ?string
    {
        $maxSizeBytes = $this->imageValidator->getMaxSize() * 1024;

        $client = new Client([
            'allow_redirects' => false,
            'timeout' => 5,
        ]);

        try {
            $response = $client->get($url);
        } catch (\Exception) {
            return null;
        }

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $contentLength = $response->getHeaderLine('Content-Length');

        if ($contentLength !== '' && (int) $contentLength > $maxSizeBytes) {
            return null;
        }

        return $response->getBody()->getContents();
    }

    private function fulfillToken(User $user, #[\SensitiveParameter] RegistrationToken $token): void
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
