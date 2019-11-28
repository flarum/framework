<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Steps;

use Flarum\Database\DatabaseMigrationRepository;
use Flarum\Database\Migrator;
use Flarum\Extension\Extension;
use Flarum\Install\Step;
use Flarum\Settings\DatabaseSettingsRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

class EnableBundledExtensions implements Step
{
    /**
     * @var ConnectionInterface
     */
    private $database;

    /**
     * @var string
     */
    private $vendorPath;

    /**
     * @var string
     */
    private $assetPath;

    public function __construct(ConnectionInterface $database, $vendorPath, $assetPath)
    {
        $this->database = $database;
        $this->vendorPath = $vendorPath;
        $this->assetPath = $assetPath;
    }

    public function getMessage()
    {
        return 'Enabling bundled extensions';
    }

    public function run()
    {
        $extensions = $this->loadExtensions();

        foreach ($extensions as $extension) {
            $extension->migrate($this->getMigrator());
            $extension->copyAssetsTo(
                new Filesystem(new Local($this->assetPath))
            );
        }

        (new DatabaseSettingsRepository($this->database))->set(
            'extensions_enabled',
            $extensions->keys()->toJson()
        );
    }

    const EXTENSION_WHITELIST = [
        'flarum-approval',
        'flarum-bbcode',
        'flarum-emoji',
        'flarum-lang-english',
        'flarum-flags',
        'flarum-likes',
        'flarum-lock',
        'flarum-markdown',
        'flarum-mentions',
        'flarum-statistics',
        'flarum-sticky',
        'flarum-subscriptions',
        'flarum-suspend',
        'flarum-tags',
    ];

    /**
     * @return \Illuminate\Support\Collection
     */
    private function loadExtensions()
    {
        $json = file_get_contents("$this->vendorPath/composer/installed.json");

        return (new Collection(json_decode($json, true)))
            ->filter(function ($package) {
                return Arr::get($package, 'type') == 'flarum-extension';
            })->filter(function ($package) {
                return ! empty(Arr::get($package, 'name'));
            })->map(function ($package) {
                $extension = new Extension($this->vendorPath.'/'.Arr::get($package, 'name'), $package);
                $extension->setVersion(Arr::get($package, 'version'));

                return $extension;
            })->filter(function (Extension $extension) {
                return in_array($extension->getId(), self::EXTENSION_WHITELIST);
            })->sortBy(function (Extension $extension) {
                return $extension->composerJsonAttribute('extra.flarum-extension.title');
            })->mapWithKeys(function (Extension $extension) {
                return [$extension->getId() => $extension];
            });
    }

    private function getMigrator()
    {
        return $this->migrator = $this->migrator ?? new Migrator(
            new DatabaseMigrationRepository($this->database, 'migrations'),
            $this->database,
            new \Illuminate\Filesystem\Filesystem
        );
    }
}
