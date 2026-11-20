<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\Tests\integration;

use Flarum\Ideation\Service\IdeaLock;
use Illuminate\Support\Collection;

/** IdeaLock, writing down each lock it takes and what the database held at that moment. */
class RecordingIdeaLock extends IdeaLock
{
    /** @var array<int, array{0: string, 1: int[], 2: mixed}> */
    public static array $log = [];

    /** @var (callable(int[]): mixed)|null what to note beside each lock */
    public static $note = null;

    public function lock(int ...$ids): Collection
    {
        // In the order the statement locks them (ORDER BY id).
        $sorted = $ids;
        sort($sorted);
        self::$log[] = ['lock', $sorted, self::$note ? (self::$note)($ids) : null];

        return parent::lock(...$ids);
    }
}
