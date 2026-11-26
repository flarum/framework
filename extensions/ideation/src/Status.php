<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation;

/**
 * The fixed set of places an idea can be.
 *
 * Fixed on purpose: notifying voters, closing voting and the board's columns
 * all reason about these, and a status a site invented could mean anything.
 * The labels are translatable, so a site can still call them what it likes.
 */
final class Status
{
    public const UNDER_REVIEW = 'under_review';
    public const PLANNED = 'planned';
    public const IN_PROGRESS = 'in_progress';
    public const SHIPPED = 'shipped';
    public const DECLINED = 'declined';

    /** Folded into another idea. Never a column, never set by hand. */
    public const MERGED = 'merged';

    /** What a moderator can set, in board order. */
    public const SETTABLE = [self::UNDER_REVIEW, self::PLANNED, self::IN_PROGRESS, self::SHIPPED, self::DECLINED];

    /** Voting is open in these and closed in the rest. */
    public const OPEN = [self::UNDER_REVIEW, self::PLANNED, self::IN_PROGRESS];

    public static function isOpen(?string $status): bool
    {
        return in_array($status, self::OPEN, true);
    }
}
