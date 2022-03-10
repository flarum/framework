<?php

namespace Flarum\PackageManager\Tests\integration;

trait ChangeComposerConfig
{
    protected function setComposerConfig(array $requirements): void
    {
        $composerSetup = new SetupComposer($requirements);
        $composerSetup->run();

        $this->composer('install');
    }
}
