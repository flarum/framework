<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling;

use Throwable;

interface Reporter
{
    /**
     * Report an error that Flarum was not able to handle to a backend.
     *
     * @param Throwable $error
     * @return void
     */
    public function report(Throwable $error);
}
