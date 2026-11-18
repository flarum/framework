<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Post\Event;

use Closure;
use Flarum\Post\Post;
use Illuminate\Database\Eloquent\Builder;

/**
 * Covers both model deletion and bulk deletion that skips model events.
 * Listeners can maintain related records within the deletion transaction.
 */
final class DeletingPosts
{
    /** @var list<Closure(): void> */
    private array $callbacks = [];

    /** @param Builder<Post> $posts */
    public function __construct(public readonly Builder $posts)
    {
    }

    /** @param Closure(): void $callback */
    public function afterDeletion(Closure $callback): void
    {
        $this->callbacks[] = $callback;
    }

    /** @internal */
    public function complete(): void
    {
        foreach ($this->callbacks as $callback) {
            $callback();
        }
    }
}
