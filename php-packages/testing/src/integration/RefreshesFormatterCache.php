<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\integration;

use Flarum\Formatter\Formatter;

trait RefreshesFormatterCache
{
    protected function tearDown(): void
    {
        $this->app()->getContainer()->make(Formatter::class)->flush();

        parent::tearDown();
    }
}
