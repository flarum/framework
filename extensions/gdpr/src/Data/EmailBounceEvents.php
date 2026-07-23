<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Data;

use Flarum\Mail\EmailBounceEvent;
use Illuminate\Support\Arr;

/**
 * Handles the user's email bounce/complaint history for GDPR export and
 * erasure. The event rows store the recipient email address (PII), so on
 * erasure that address must be scrubbed; the rows themselves are retained
 * (email nulled) so aggregate bounce statistics stay meaningful.
 */
class EmailBounceEvents extends Type
{
    public static function piiFields(): array
    {
        return ['email'];
    }

    public function export(): ?array
    {
        $exportData = [];

        EmailBounceEvent::query()
            ->where('user_id', $this->user->id)
            ->each(function (EmailBounceEvent $event) use (&$exportData) {
                $exportData[] = [
                    "email-bounce-events/event-{$event->id}.json" => $this->encodeForExport(
                        Arr::except($event->toArray(), ['user_id'])
                    ),
                ];
            });

        return $exportData;
    }

    /**
     * Strip the PII (email address and any provider reason text) from the
     * user's bounce events, but keep the rows so historical bounce volume and
     * "recovered" counts remain accurate.
     */
    public function anonymize(): void
    {
        EmailBounceEvent::query()
            ->where('user_id', $this->user->id)
            ->update([
                'email' => '',
                'reason' => null,
            ]);
    }

    public function delete(): void
    {
        EmailBounceEvent::query()
            ->where('user_id', $this->user->id)
            ->delete();
    }
}
