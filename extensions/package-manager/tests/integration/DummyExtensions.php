<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\integration;

trait DummyExtensions
{
    protected function makeDummyExtensionCompatibleWith(string $name, string $coreVersions): void
    {
        $dirName = $this->tmpDir().'/packages/'.str_replace('/', '-', $name);

        if (! file_exists($dirName)) {
            mkdir($dirName);
        }

        file_put_contents($dirName.'/composer.json', json_encode([
            'name' => $name,
            'version' => '1.0.0',
            'require' => [
                'flarum/core' => $coreVersions
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
}
