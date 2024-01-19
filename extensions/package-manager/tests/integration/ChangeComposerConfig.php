<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\integration;

trait ChangeComposerConfig
{
    protected function setComposerConfig(array $requirements): void
    {
        $composerSetup = new SetupComposer($requirements);
        $composerSetup->run();

        $this->composer('install');
    }
}
