<?php

declare(strict_types=1);

namespace Flarum\Composer\Plugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Flarum\Http\Middleware\ClearOPCache;

class ComposerPlugin implements PluginInterface, EventSubscriberInterface
{
    private Composer $composer;

    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
    }

    public function deactivate(Composer $composer, IOInterface $io): void {}
    public function uninstall(Composer $composer, IOInterface $io): void {}


    public static function getSubscribedEvents(): array
    {
        return [
            'post-autoload-dump' => 'flag',
        ];
    }

    public function flag(): void
    {
        $path = class_exists(ClearOPCache::class) ? ClearOPCache::PATH : '/storage/cache/clear-opcache';

        $vendorDir  = $this->composer->getConfig()->get('vendor-dir');
        $flagFile   = dirname($vendorDir) . $path;

        if (is_dir(dirname($flagFile)) && ! file_exists($flagFile)) {
            @file_put_contents($flagFile, (string) time());
        }
    }
}
