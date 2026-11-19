<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Testing\Fixtures\Extend;

use Flarum\Foundation\ErrorHandling\Reporter;
use Throwable;

class RecordingReporter implements Reporter
{
    /**
     * @var list<string>
     */
    public static array $messages = [];

    public static function reset(): void
    {
        static::$messages = [];
    }

    public function report(Throwable $error): void
    {
        static::$messages[] = $error->getMessage();
    }
}
