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
use Flarum\Foundation\MaintenanceMode;
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
        MaintenanceMode $maintenance,
        array $enabledIds
    ) {
        parent::__construct($config, $paths, $container, $migrator, $dispatcher, $filesystem, $maintenance);

        $this->enabledIds = $enabledIds;
    }

    public function getExtensions(): Collection
    {
        $extensions = parent::getExtensions();

        $package = json_decode($this->filesystem->get($this->paths->vendor.'/../composer.json'), true);
        $packagePath = $this->paths->vendor.'/../';

        $extensions = $this->includeCurrentExtension($extensions, $package, $packagePath);

        return $this->extensions = $this->includeMonorepoExtensions($extensions, $package, $packagePath);
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
        $adapter = new LocalFilesystemAdapter($this->paths->public.'/assets');

        return new FilesystemAdapter(new \League\Flysystem\Filesystem($adapter), $adapter);
    }

    protected function includeCurrentExtension(Collection $extensions, $package, string $packagePath): Collection
    {
        if (Arr::get($package, 'type') === 'flarum-extension') {
            $current = new Extension($packagePath, $package);
            $current->setInstalled(true);
            $current->setVersion(Arr::get($package, 'version', '0.0'));
            $current->calculateDependencies([]);

            $extensions->put($current->getId(), $current);

            $extensions = $extensions->sortBy(function ($extension, $name) {
                return $extension->composerJsonAttribute('extra.flarum-extension.title');
            });
        }

        return $extensions;
    }

    /**
     * Allows symlinking the vendor directory in extensions when running tests on monorepos.
     */
    protected function includeMonorepoExtensions(Collection $extensions, $package, string $packagePath): Collection
    {
        foreach ($this->subExtensionConfsFromJson($package, $packagePath) ?? [] as $path => $package) {
            $extension = $this->extensionFromJson($package, $path);
            $extension->calculateDependencies([]);
            $extensions->put($extension->getId(), $extension);
        }

        return $extensions;
    }
}
