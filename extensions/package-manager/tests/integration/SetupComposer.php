<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Tests\integration;

use Flarum\Testing\integration\UsesTmpDir;

class SetupComposer
{
    use UsesTmpDir;

    public function run()
    {
        $filePath = $this->tmpDir().'/composer.json';

        file_put_contents($filePath, json_encode([
            'require' => [
                'flarum/core' => '1.0.0',
                'flarum/tags' => '1.0.3',
                'flarum/lang-english' => '*',
            ],
            'config' => [
                'preferred-install' => 'dist',
                'sort-packages' => true,
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }
}
