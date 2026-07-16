<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags;

use Flarum\Audit\AuditLogger;
use Flarum\Flags\Event\Dismissed;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Audit log integration for flarum/flags.
 *
 * Hooks the Flag model's created event and the Dismissed event. Wired into
 * flarum/audit through the Flarum\Audit\Extend\Audit extender's `using()` escape hatch, behind
 * an Extend\Conditional so it's only active when flarum-audit is installed.
 */
class AuditIntegration
{
    /**
     * @var string[]
     */
    public static array $actions = ['post.flagged', 'post.dismissed_flags'];

    public function __invoke(Container $container): void
    {
        // Listen on the events dispatcher rather than the static Flag::created(Closure) API, so the
        // listener isn't bound to the model's static dispatcher.
        $container->make(Dispatcher::class)->listen('eloquent.created: '.Flag::class, [$this, 'flagCreated']);

        $container->make(Dispatcher::class)->listen(Dismissed::class, [$this, 'flagsDismissed']);
    }

    public function flagsDismissed(Dismissed $event): void
    {
        AuditLogger::log('post.dismissed_flags', [
            'discussion_id' => $event->post->discussion->id,
            'post_id' => $event->post->id,
        ]);
    }

    public function flagCreated(Flag $flag): void
    {
        // We only log flags created manually via the extension.
        // We don't log the creation of Approval/Akismet flags.
        if ($flag->type !== 'user') {
            return;
        }

        AuditLogger::log('post.flagged', [
            'discussion_id' => $flag->post->discussion->id,
            'post_id' => $flag->post->id,
            'reason' => $flag->reason ?? ($flag->reason_detail ? 'other' : null),
        ]);
    }
}
