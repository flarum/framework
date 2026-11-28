<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Steps;

use Flarum\Formatter\XsltPolyfill;
use Flarum\Install\ReversibleStep;
use Illuminate\Filesystem\Filesystem;

readonly class PublishAssets implements ReversibleStep
{
    public function __construct(
        private string $vendorPath,
        private string $assetPath
    ) {
    }

    public function getMessage(): string
    {
        return 'Publishing all assets';
    }

    public function run(): void
    {
        $filesystem = new Filesystem;

        $filesystem->copyDirectory(
            "$this->vendorPath/flarum/font-awesome/webfonts",
            "$this->assetPath/fonts"
        );

        if ($sourceDir = XsltPolyfill::findSource()) {
            foreach (XsltPolyfill::FILES as $source => $target) {
                if ($filesystem->exists("$sourceDir/$source")) {
                    $filesystem->ensureDirectoryExists(dirname("$this->assetPath/$target"));
                    $filesystem->copy("$sourceDir/$source", "$this->assetPath/$target");
                }
            }
        }
    }

    public function revert(): void
    {
        $filesystem = new Filesystem;

        $filesystem->deleteDirectory("$this->assetPath/fonts");
        $filesystem->deleteDirectory("$this->assetPath/xslt-polyfill");
    }
}
