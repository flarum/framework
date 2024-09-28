<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages;

use Flarum\Formatter\Formatter;
use Flarum\Foundation\AbstractServiceProvider;

class DialogServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(Formatter $formatter): void
    {
        DialogMessage::setFormatter($formatter);
    }
}
