<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ideation\GitHub;

use RuntimeException;

/**
 * GitHub will not answer for this one item, and asking again soon will not
 * change that: its repository was renamed or transferred (GitHub answers
 * with a redirect, which is never followed), or it is blocked. Unlike
 * Unavailable, other items can still be asked for.
 */
class Gone extends RuntimeException
{
}
