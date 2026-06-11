<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Integration;

use Flarum\Audit\AuditLogger;
use Flarum\Flags\Flag;
use Flarum\Post\Post;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * flarum/flags integration.
 *
 * Stateful: hooks the Flag model and a HasMany macro to detect flag dismissals, so it is
 * wired through the audit extender's `using()` escape hatch.
 */
class FlagsIntegration
{
    /**
     * @var string[]
     */
    public static $actions = ['post.flagged', 'post.dismissed_flags'];

    public function __invoke(Container $container): void
    {
        if (! class_exists(Flag::class)) {
            return;
        }

        Flag::created(function (Flag $flag) {
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
        });

        // We don't use the FlagsWillBeDeleted event as extensions might still prevent deletion at that point.
        HasMany::macro('delete', function () {
            /** @var HasMany $this */
            $parent = $this->getParent();

            // Because flarum/flags calls this every time a post is deleted, we need to check if there were actual flags.
            $post = ($parent instanceof Post && $this->getQuery()->getModel() instanceof Flag && $this->getQuery()->count())
                ? $parent
                : null;

            // Replicates code from Relation::__call
            $result = $this->forwardCallTo($this->getQuery(), 'delete', func_get_args());

            if ($post) {
                AuditLogger::log('post.dismissed_flags', [
                    'discussion_id' => $post->discussion->id,
                    'post_id' => $post->id,
                ]);
            }

            // Replicates code from Relation::__call
            if ($result === $this->getQuery()) {
                return $this;
            }

            return $result;
        });
    }
}
