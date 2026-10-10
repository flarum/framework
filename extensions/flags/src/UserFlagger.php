<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags;

use Flarum\Flags\Event\UserFlagCreated;
use Flarum\Foundation\ValidationException;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Flarum\User\UserRepository;
use Illuminate\Contracts\Events\Dispatcher;

/** Programmatic account reporting for trusted server-side extensions. */
class UserFlagger
{
    public function __construct(protected Dispatcher $events, protected SettingsRepositoryInterface $settings, protected UserRepository $users)
    {
    }

    public function assertCanFlag(User $target, User $actor): void
    {
        $actor->assertRegistered();
        $this->users->findOrFail($target->id, $actor);
        $actor->assertCan('flag', $target);

        if ($target->id === $actor->id && ! $this->settings->get('flarum-flags.can_flag_own')) {
            throw new PermissionDeniedException;
        }
    }

    /**
     * A null actor represents a trusted extension, not an anonymous API user.
     * Repeated reports of the same type/actor/target update the existing unresolved flag.
     */
    public function flag(User $target, string $type, ?string $reason = null, ?string $detail = null, ?User $actor = null): Flag
    {
        if (! $target->exists || ! $target->id || ! $type || mb_strlen($type) > 255) {
            throw new ValidationException(['type' => 'A persisted user and nonempty flag type are required.']);
        }
        if ((! $reason && ! $detail) || mb_strlen($reason ?? '') > 255 || mb_strlen($detail ?? '') > 2000) {
            throw new ValidationException(['reason' => 'Provide a reason (up to 255 characters) or detail (up to 2000 characters).']);
        }
        if ($actor) {
            $this->assertCanFlag($target, $actor);
        }

        $flag = Flag::query()->firstOrNew([
            'post_id' => null,
            'target_user_id' => $target->id,
            'type' => $type,
            'user_id' => $actor?->id,
        ]);
        $flag->reason = $reason;
        $flag->reason_detail = $detail;
        $new = ! $flag->exists;
        $flag->save();

        if ($new) {
            $this->events->dispatch(new UserFlagCreated($flag, $actor));
        }

        return $flag;
    }
}
