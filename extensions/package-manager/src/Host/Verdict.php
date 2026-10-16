<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Host;

/**
 * Is this update to blame for the site being down?
 *
 * 🚨 A pure rule, on its own, because it is the dangerous part.
 *
 * Everything else here is plumbing — a curl call, a log tail — and if the
 * plumbing misbehaves the worst case is a check that does not run. This rule
 * decides whether to UNDO somebody's update, so the way it fails is by throwing
 * away a good one, and the two cases it must never confuse are "the site broke"
 * and "the site was already broken".
 *
 * Separated from the network so every combination can be asserted, rather than
 * only the ones a test host happens to be able to produce.
 */
final class Verdict
{
    /** Nothing to judge by: the check could not run before OR after. */
    public const NOT_JUDGED = 'not-judged';

    /** The site answers. Nothing to do. */
    public const HEALTHY = 'healthy';

    /** It answered before and does not now. This update is the difference. */
    public const BROKEN_BY_THIS = 'broken-by-this';

    /** It was not answering before either, so this update is not the cause. */
    public const ALREADY_BROKEN = 'already-broken';

    /**
     * @param string $before 'ok' | 'down' | 'unchecked' — what was seen BEFORE
     * @param bool   $okNow  whether the site answers now
     */
    public static function from(string $before, bool $okNow): string
    {
        if ($before === 'unchecked') {
            /*
             * 🚨 Never BROKEN_BY_THIS. Without a before, a site that is down
             * now might have been down for a week — and this branch is reached
             * precisely on the hosts where the checker itself does not work, so
             * it would fire on every update on that host and undo all of them.
             */
            return self::NOT_JUDGED;
        }

        if ($okNow) {
            return self::HEALTHY;
        }

        return $before === 'ok' ? self::BROKEN_BY_THIS : self::ALREADY_BROKEN;
    }
}
