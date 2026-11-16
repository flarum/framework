<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Exception;

/**
 * Which token limit stopped a call.
 */
enum LimitType: string
{
    /** The connection's limit for the calendar month. */
    case Connection = 'connection';

    /** The feature's limit for the day. */
    case Feature = 'feature';

    /** The member's limit for the day, across every feature. */
    case Member = 'member';

    /** The limit for the day shared by every guest, across every feature. */
    case Guests = 'guests';
}
