<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\integration\extend\Fixtures;

use Flarum\Foundation\ErrorHandling\Reporter;
use Throwable;

class RecordingReporter implements Reporter
{
    /**
     * @var list<string>
     */
    public static array $messages = [];

    public function report(Throwable $error): void
    {
        static::$messages[] = $error->getMessage();
    }
}
