<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Steps;

use Flarum\Install\ReversibleStep;
use Flarum\Install\Step;
use Illuminate\Filesystem\Filesystem;

class PublishAssets implements Step, ReversibleStep
{
    /**
     * @var string
     */
    private $vendorPath;

    /**
     * @var string
     */
    private $assetPath;

    public function __construct($vendorPath, $assetPath)
    {
        $this->vendorPath = $vendorPath;
        $this->assetPath = $assetPath;
    }

    public function getMessage()
    {
        return 'Publishing all assets';
    }

    public function run()
    {
        (new Filesystem)->copyDirectory(
            "$this->vendorPath/components/font-awesome/webfonts",
            $this->targetPath()
        );
    }

    public function revert()
    {
        (new Filesystem)->deleteDirectory($this->targetPath());
    }

    private function targetPath()
    {
        return "$this->assetPath/fonts";
    }
}
