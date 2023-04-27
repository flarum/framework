<?php

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
