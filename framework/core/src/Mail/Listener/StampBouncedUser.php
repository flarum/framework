<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail\Listener;

use Carbon\Carbon;
use Flarum\Mail\EmailBounceEvent;
use Flarum\Mail\Event\EmailBounced;
use Flarum\Mail\Event\EmailComplained;
use Flarum\User\User;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Reacts to bounce/complaint events by (a) logging a historical event row —
 * which persists even after the user fixes their address — and (b) flagging
 * the affected user so mail can be suppressed and the state surfaced to admins.
 */
class StampBouncedUser
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(EmailBounced::class, [$this, 'whenEmailBounced']);
        $events->listen(EmailComplained::class, [$this, 'whenEmailComplained']);
    }

    public function whenEmailBounced(EmailBounced $event): void
    {
        $this->record($event->email, $event->recipient, EmailBounceEvent::TYPE_BOUNCE, $event->reason ?: 'bounced');
    }

    public function whenEmailComplained(EmailComplained $event): void
    {
        $this->record($event->email, $event->recipient, EmailBounceEvent::TYPE_COMPLAINT, $event->reason ?: 'complaint');
    }

    protected function record(string $email, ?User $user, string $type, string $reason): void
    {
        $now = Carbon::now();

        // Always log the event, even if the address maps to no user.
        EmailBounceEvent::create([
            'email' => $email,
            'user_id' => $user?->id,
            'type' => $type,
            'reason' => $reason,
            'created_at' => $now,
        ]);

        if ($user === null) {
            return;
        }

        $user->email_bounced_at = $now;
        $user->email_bounce_reason = $reason;
        $user->save();
    }
}
