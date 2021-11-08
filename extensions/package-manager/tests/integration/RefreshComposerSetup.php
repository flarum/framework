<?php

namespace Flarum\PackageManager\Tests\integration;

trait RefreshComposerSetup
{
    public function tearDown(): void
    {
        $composerSetup = new SetupComposer();
        @unlink($this->tmpDir().'/composer.lock');

        $composerSetup->run();

        $this->composer('install');

        parent::tearDown();
    }
}
