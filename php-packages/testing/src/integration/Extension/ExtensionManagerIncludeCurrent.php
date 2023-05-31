<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Testing\integration\Extension;

use Flarum\Database\Migrator;
use Flarum\Extension\Extension;
use Flarum\Extension\ExtensionManager;
use Flarum\Foundation\Paths;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use League\Flysystem\Local\LocalFilesystemAdapter;

class ExtensionManagerIncludeCurrent extends ExtensionManager
{
    /**
     * @var array
     */
    protected $enabledIds;

    /**
     * @var bool
     */
    public $booted = false;

    public function __construct(
        SettingsRepositoryInterface $config,
        Paths $paths,
        Container $container,
        Migrator $migrator,
        Dispatcher $dispatcher,
        Filesystem $filesystem,
        array $enabledIds
    ) {
        parent::__construct($config, $paths, $container, $migrator, $dispatcher, $filesystem);

        $this->enabledIds = $enabledIds;
    }

    public function getExtensions(): Collection
    {
        $extensions = parent::getExtensions();

        $package = json_decode($this->filesystem->get($this->paths->vendor.'/../composer.json'), true);

        if (Arr::get($package, 'type') === 'flarum-extension') {
            $current = new Extension($this->paths->vendor.'/../', $package);
            $current->setInstalled(true);
            $current->setVersion(Arr::get($package, 'version', '0.0'));
            $current->calculateDependencies([]);

            $extensions->put($current->getId(), $current);

            $this->extensions = $extensions->sortBy(function ($extension) {
                return $extension->composerJsonAttribute('extra.flarum-extension.title');
            });
        }

        return $this->extensions;
    }

    /**
     * We assume it's not enabled during boot.
     * However, since some logic needs this, as soon as we enable extensions
     * we'll switch booted to on.
     */
    public function isEnabled($extension): bool
    {
        if (! $this->booted) {
            return false;
        }

        return parent::isEnabled($extension);
    }

    /**
     * In test cases, enabled extensions are determined by the test case, not the database.
     */
    public function getEnabled(): array
    {
        return $this->enabledIds;
    }

    /**
     * Enabled extensions must be specified by the test case, so this should do nothing.
     */
    protected function setEnabledExtensions(array $enabledExtensions): void
    {
    }

    /**
     * Get an instance of the assets filesystem.
     * This is resolved dynamically because Flarum's filesystem configuration
     * might not be booted yet when the ExtensionManager singleton initializes.
     */
    protected function getAssetsFilesystem(): Cloud
    {
        $adaptor = new LocalFilesystemAdapter($this->paths->public.'/assets');
        $filesystem = new \League\Flysystem\Filesystem($adaptor);

        return new FilesystemAdapter($filesystem, $adaptor);
    }
}
