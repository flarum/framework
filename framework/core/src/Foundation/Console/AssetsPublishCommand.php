<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\Console;

use Flarum\Console\AbstractCommand;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Paths;
use Illuminate\Contracts\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;

class AssetsPublishCommand extends AbstractCommand
{
    public function __construct(
        protected Container $container,
        protected Paths $paths
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('assets:publish')
            ->setDescription('Publish core and extension assets.');
    }

    protected function fire(): int
    {
        $this->info('Publishing core assets...');

        $target = $this->container->make('filesystem')->disk('flarum-assets');
        $local = new Filesystem();

        $pathPrefix = $this->paths->vendor.'/components/font-awesome/webfonts';
        $assetFiles = $local->allFiles($pathPrefix);

        foreach ($assetFiles as $fullPath) {
            $relPath = substr($fullPath, strlen($pathPrefix));
            $target->put("fonts/$relPath", $local->get($fullPath));
        }

        $this->info('Publishing extension assets...');

        $extensions = $this->container->make(ExtensionManager::class);
        $extensions->getMigrator()->setOutput($this->output);

        foreach ($extensions->getEnabledExtensions() as $name => $extension) {
            if ($extension->hasAssets()) {
                $this->info('Publishing for extension: '.$name);
                $extension->copyAssetsTo($target);
            }
        }

        return Command::SUCCESS;
    }
}
