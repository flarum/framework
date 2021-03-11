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
use Illuminate\Support\Arr;

class ExtensionManagerIncludeCurrent extends ExtensionManager
{
    /**
     * @return Collection
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
}
