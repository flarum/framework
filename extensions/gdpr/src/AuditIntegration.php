<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr;

use Flarum\Audit\AuditLogger;
use Flarum\Gdpr\Events\Erased;
use Flarum\Gdpr\Events\Exported;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\User\User;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Audit log integration for flarum/gdpr.
 *
 * Records the two privacy-sensitive operations — erasure (deletion/anonymization) and export —
 * in the audit log. Wired into flarum/audit through the Flarum\Audit\Extend\Audit extender's
 * `using()` escape hatch, behind an Extend\Conditional so it's only active when flarum-audit is
 * installed.
 *
 * Both operations run in queued jobs, where the request-scoped AuditLogger::$actor is not set.
 * We therefore set the actor explicitly before logging (the same pattern core integrations use,
 * e.g. CoreUserIntegration::loggedIn): the erasure is attributed to whoever processed it
 * (ErasureRequest::processed_by — null for the scheduled "system" path), and the export to the
 * actor who requested it.
 */
class AuditIntegration
{
    /**
     * @var string[]
     */
    public static array $actions = [
        'user.gdpr_anonymized',
        'user.gdpr_deleted',
        'user.gdpr_exported',
    ];

    public function __invoke(Container $container): void
    {
        $events = $container->make(Dispatcher::class);

        $events->listen(Erased::class, [$this, 'erased']);
        $events->listen(Exported::class, [$this, 'exported']);
    }

    public function erased(Erased $event): void
    {
        // Map the erasure mode to a distinct action string.
        $action = $event->mode === ErasureRequest::MODE_ANONYMIZATION
            ? 'user.gdpr_anonymized'
            : 'user.gdpr_deleted';

        // Who processed it: the admin's id, or null when the scheduled task processed it.
        $processedBy = $event->request?->processed_by;

        // Attribute the log entry to the processor. A null processor (system erasure) leaves the
        // actor null, which the audit log renders as a system/guest action.
        AuditLogger::$actor = $processedBy ? User::find($processedBy) : null;

        AuditLogger::log($action, [
            'user_id' => $event->user->id,
            'processed_by' => $processedBy,
        ]);
    }

    public function exported(Exported $event): void
    {
        AuditLogger::$actor = $event->actor;

        AuditLogger::log('user.gdpr_exported', [
            'user_id' => $event->user->id,
        ]);
    }
}
