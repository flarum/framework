<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Likes\Jobs;

use Flarum\Likes\LikeCount;
use Flarum\Queue\AbstractJob;

class RefreshLikeCounts extends AbstractJob
{
    public function handle(LikeCount $counts): void
    {
        $counts->refreshAll();
    }
}
