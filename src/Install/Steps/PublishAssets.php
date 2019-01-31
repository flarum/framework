<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    private $basePath;

    /**
     * @var string
     */
    private $assetPath;

    public function __construct($basePath, $assetPath)
    {
        $this->basePath = $basePath;
        $this->assetPath = $assetPath;
    }

    public function getMessage()
    {
        return 'Publishing all assets';
    }

    public function run()
    {
        (new Filesystem)->copyDirectory(
            "$this->basePath/vendor/components/font-awesome/webfonts",
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
