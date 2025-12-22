<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Steps;

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
        (new Filesystem)->copyDirectory(
            "$this->vendorPath/components/font-awesome/webfonts",
            $this->targetPath()
        );
    }

    public function revert(): void
    {
        (new Filesystem)->deleteDirectory($this->targetPath());
    }

    private function targetPath(): string
    {
        return "$this->assetPath/fonts";
    }
}
