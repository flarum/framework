<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Integration;

use Flarum\Audit\AuditLogger;
use Flarum\User\Event;
use Flarum\User\LoginProvider;
use Flarum\User\PasswordToken;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Core user event logging.
 *
 * This integration is stateful (it captures the original email before it changes and
 * relies on Eloquent model events), so it is wired through the audit extender's `using()`
 * escape hatch rather than plain `listen()` callbacks.
 */
class CoreUserIntegration
{
    /**
     * @var string[]
     */
    public static array $actions = [
        'user.activated',
        'user.activated_with_email',
        'user.avatar_changed',
        'user.avatar_removed',
        'user.created',
        'user.deleted',
        'user.email_changed',
        'user.email_change_requested',
        'user.groups_changed',
        'user.logged_in',
        'user.logged_in_with_provider',
        'user.logged_out',
        'user.password_changed',
        'user.password_change_requested',
        'user.provider_connected',
        'user.username_changed',
    ];

    protected ?string $originalEmail = null;

    public function __invoke(Container $container): void
    {
        $events = $container->make(Dispatcher::class);

        $events->listen(Event\Activated::class, [$this, 'activated']);
        $events->listen(Event\AvatarChanged::class, [$this, 'avatarChanged']);
        $events->listen(Event\Deleted::class, [$this, 'deleted']);
        $events->listen(Event\EmailChangeRequested::class, [$this, 'emailChangeRequested']);
        $events->listen(Event\EmailChanged::class, [$this, 'emailChanged']);
        $events->listen(Event\GroupsChanged::class, [$this, 'groupsChanged']);
        $events->listen(Event\LoggedIn::class, [$this, 'loggedIn']);
        $events->listen(Event\LoggedOut::class, [$this, 'loggedOut']);
        $events->listen(Event\PasswordChanged::class, [$this, 'passwordChanged']);
        $events->listen(Event\Registered::class, [$this, 'registered']);
        $events->listen(Event\Renamed::class, [$this, 'renamed']);

        // These hook Eloquent model lifecycle events. We listen on the events dispatcher
        // (rather than the static Model::event(Closure) API) so the listeners aren't bound to
        // the model classes' static dispatcher — which can't be serialized under PHPUnit
        // process isolation.
        $events->listen('eloquent.created: '.PasswordToken::class, [$this, 'passwordTokenCreated']);
        $events->listen('eloquent.created: '.LoginProvider::class, [$this, 'loginProviderCreated']);
        $events->listen('eloquent.updated: '.LoginProvider::class, [$this, 'loginProviderUpdated']);
        $events->listen('eloquent.saving: '.User::class, [$this, 'userSaving']);
    }

    protected function log(User $user, string $action, array $payload = []): void
    {
        AuditLogger::log("user.$action", array_merge([
            'user_id' => $user->id,
        ], $payload));
    }

    public function passwordTokenCreated(PasswordToken $token): void
    {
        $this->log($token->user, 'password_change_requested');
    }

    public function loginProviderCreated(LoginProvider $provider): void
    {
        $this->log($provider->user, 'provider_connected', [
            'provider' => $provider->provider,
            'identifier' => $provider->identifier,
        ]);
    }

    public function loginProviderUpdated(LoginProvider $provider): void
    {
        if (Arr::exists($provider->getChanges(), 'last_login_at')) {
            $this->log($provider->user, 'logged_in_with_provider', [
                'provider' => $provider->provider,
                'identifier' => $provider->identifier,
            ]);
        }
    }

    public function userSaving(User $user): void
    {
        // There's no way of accessing the original email from EmailChanged, so we save it beforehand.
        // We can't use the core user saving event because it's not dispatched in ConfirmEmailHandler.
        $this->originalEmail = $user->getOriginal('email');
    }

    public function activated(Event\Activated $event): void
    {
        // Do not log anything when enabled via API on creation or via social login
        if ($event->user->wasRecentlyCreated) {
            return;
        }

        if (Str::startsWith(AuditLogger::$path, '/confirm/')) {
            $this->log($event->user, 'activated_with_email');
        } else {
            $this->log($event->user, 'activated');
        }
    }

    public function avatarChanged(Event\AvatarChanged $event): void
    {
        // Do not log anything when avatar is added via API on creation or via social login
        if ($event->user->wasRecentlyCreated) {
            return;
        }

        $this->log($event->user, $event->user->avatar_url ? 'avatar_changed' : 'avatar_removed');
    }

    public function deleted(Event\Deleted $event): void
    {
        $this->log($event->user, 'deleted');
    }

    public function emailChangeRequested(Event\EmailChangeRequested $event): void
    {
        $this->log($event->user, 'email_change_requested', [
            'new_email' => $event->email,
        ]);
    }

    public function emailChanged(Event\EmailChanged $event): void
    {
        $this->log($event->user, 'email_changed', [
            'old_email' => $this->originalEmail,
            'new_email' => $event->user->email,
        ]);
    }

    public function groupsChanged(Event\GroupsChanged $event): void
    {
        $oldGroupIds = Arr::pluck($event->oldGroups, 'id');
        // The event only carries the old groups; query the relation fresh for the new set
        // rather than reading the (stale, pre-change) preloaded $user->groups.
        $newGroupIds = $event->user->groups()->pluck('groups.id');

        if (json_encode($oldGroupIds) !== json_encode($newGroupIds)) {
            $this->log($event->user, 'groups_changed', [
                'old_group_ids' => $oldGroupIds,
                'new_group_ids' => $newGroupIds,
            ]);
        }
    }

    public function loggedIn(Event\LoggedIn $event): void
    {
        AuditLogger::$actor = $event->user;
        $this->log($event->user, 'logged_in');
    }

    public function loggedOut(Event\LoggedOut $event): void
    {
        $this->log($event->user, 'logged_out');
    }

    public function passwordChanged(Event\PasswordChanged $event): void
    {
        $this->log($event->user, 'password_changed');
    }

    public function registered(Event\Registered $event): void
    {
        $this->log($event->user, 'created');
    }

    public function renamed(Event\Renamed $event): void
    {
        $this->log($event->user, 'username_changed', [
            'old_username' => $event->oldUsername,
            'new_username' => $event->user->username,
        ]);
    }
}
