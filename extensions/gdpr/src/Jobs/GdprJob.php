<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Jobs;

use Flarum\Queue\AbstractJob;

abstract class GdprJob extends AbstractJob
{
    public static ?string $onQueue = null;
}
