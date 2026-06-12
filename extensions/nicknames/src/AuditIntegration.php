<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Nicknames;

use Flarum\Audit\AuditLogger;
use Flarum\User\Event\Saving;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Arr;

/**
 * Audit log integration for flarum/nicknames.
 *
 * Stateful: there's no dedicated event for nickname changes, so we capture the old value
 * during the user saving event and emit the log afterwards. Wired into flarum/audit through
 * the Flarum\Audit\Extend\Audit extender's `using()` escape hatch, behind an Extend\Conditional
 * so it's only active when flarum-audit is installed.
 */
class AuditIntegration
{
    /**
     * @var string[]
     */
    public static array $actions = ['user.nickname_changed'];

    protected string|false $originalNickname = false;

    public function __invoke(Container $container): void
    {
        // We need to register the event listener in booted() because that's where Extend\Event
        // registers them. Ours should run after Nicknames because of the extension's optional
        // dependency tree. The audit extender already defers this callback until booted().
        $events = $container->make(Dispatcher::class);

        $events->listen(Saving::class, [$this, 'saving']);

        // There's no event for the nickname change at this time so we hook the user saved
        // lifecycle. We listen on the dispatcher rather than User::saved(Closure) so the
        // listener isn't bound to the model's static dispatcher.
        $events->listen('eloquent.saved: '.User::class, [$this, 'userSaved']);
    }

    public function userSaved(User $user): void
    {
        // The $originalNickname variable holds the old value but it also signifies that the nickname was updated.
        if ($this->originalNickname !== false) {
            AuditLogger::log('user.nickname_changed', [
                'user_id' => $user->id,
                'old_nickname' => $this->originalNickname ?: null,
                'new_nickname' => $user->nickname ?: null,
            ]);
        }
    }

    public function saving(Saving $event): void
    {
        $attributes = (array) Arr::get($event->data, 'attributes');

        if (isset($attributes['nickname']) && $event->user->isDirty('nickname')) {
            $this->originalNickname = $event->user->getOriginal('nickname');
        } else {
            // Reset in case other user operations happen during the same lifecycle, we don't need multiple logs.
            $this->originalNickname = false;
        }
    }
}
