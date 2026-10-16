<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Run;

use RuntimeException;

/**
 * The step found the site broken, put everything back, and is saying so.
 *
 * 🚨 Distinct from an ordinary failure, and the distinction is not cosmetic. A
 * failed run leaves the admin screen offering a Roll back button; a run that
 * has ALREADY rolled itself back must not, or the first thing a worried admin
 * does is unwind a tree that is already correct.
 */
class Reverted extends RuntimeException
{
    /** @param list<string> $undone */
    public function __construct(string $why, public readonly array $undone = [])
    {
        parent::__construct($why);
    }
}
