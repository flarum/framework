<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags;

use Flarum\Audit\AuditLogger;
use Flarum\Post\Post;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Audit log integration for flarum/flags.
 *
 * Stateful: hooks the Flag model and a HasMany macro to detect flag dismissals. Wired into
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

        // We don't use the FlagsWillBeDeleted event as extensions might still prevent deletion at that
        // point. This macro must stay a closure: it relies on $this being rebound to the HasMany
        // instance at call time. It registers on the HasMany class (not a model instance), so it is
        // not part of the serialized model graph that tripped the static model-event closures.
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
