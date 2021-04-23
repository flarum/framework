<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\integration\Extension;

use Flarum\Extension\Extension;
use Flarum\Extension\ExtensionManager;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class ExtensionManagerIncludeCurrent extends ExtensionManager
{
    /**
     * @{@inheritDoc}
     */
    public function getExtensions()
    {
        $extensions = parent::getExtensions();

        $package = json_decode($this->filesystem->get($this->paths->vendor . '/../composer.json'), true);

        if (Arr::get($package, 'type') === 'flarum-extension') {
            $current = new Extension($this->paths->vendor . '/../', $package);
            $current->setInstalled(true);
            $current->setVersion(Arr::get($package, 'version'));
            $current->calculateDependencies([], []);

            $extensions->put($current->getId(), $current);

            $this->extensions = $extensions->sortBy(function ($extension, $name) {
                return $extension->composerJsonAttribute('extra.flarum-extension.title');
            });
        }

        return $this->extensions;
    }

    /**
     * Get an instance of the assets filesystem.
     * This is resolved dynamically because Flarum's filesystem configuration
     * might not be booted yet when the ExtensionManager singleton initializes.
     */
    protected function getAssetsFilesystem(): Cloud
    {
        return new FilesystemAdapter(new Filesystem(new Local($this->paths->public.'/assets'), ['url' => resolve('flarum.config')->url().'/assets']));
    }
}
