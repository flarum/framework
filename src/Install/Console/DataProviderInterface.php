<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Console;

use Flarum\Install\Installation;

interface DataProviderInterface
{
    public function configure(Installation $installation): Installation;
}
