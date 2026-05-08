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
use Illuminate\Contracts\Filesystem\Cloud;
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

        $pathPrefix = $this->paths->vendor.'/fortawesome/font-awesome/webfonts';
        $assetFiles = $local->allFiles($pathPrefix);

        foreach ($assetFiles as $fullPath) {
            $relPath = substr($fullPath, strlen($pathPrefix));
            $target->put("fonts/$relPath", $local->get($fullPath));
        }

        $this->publishXsltPolyfill($target, $local);

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

    /**
     * Copies the xslt-polyfill bundle into the public assets disk so the
     * Formatter can hand the browser a public URL when native XSLT is
     * disabled. Both files are kept in their original relative layout
     * (root + ./dist) so the polyfill's currentScript-based wasm loader
     * keeps working.
     */
    private function publishXsltPolyfill(Cloud $target, Filesystem $local): void
    {
        $sourceDir = $this->findXsltPolyfillSource();

        if ($sourceDir === null) {
            $this->info('xslt-polyfill not found in node_modules; skipping.');
            return;
        }

        $files = [
            'xslt-polyfill.min.js' => 'xslt-polyfill/xslt-polyfill.min.js',
            'dist/xslt-wasm.js' => 'xslt-polyfill/dist/xslt-wasm.js',
        ];

        foreach ($files as $relSource => $relTarget) {
            $sourcePath = "$sourceDir/$relSource";
            if ($local->exists($sourcePath)) {
                $target->put($relTarget, $local->get($sourcePath));
            }
        }
    }

    private function findXsltPolyfillSource(): ?string
    {
        // From AssetsPublishCommand:
        //   Per-package install:  ../../../js/node_modules/xslt-polyfill
        //   Monorepo hoist:       ../../../../../node_modules/xslt-polyfill
        // From a published `vendor/flarum/core` install the per-package path
        // resolves correctly; the monorepo path only matters during framework
        // development.
        $candidates = [
            __DIR__.'/../../../js/node_modules/xslt-polyfill',
            __DIR__.'/../../../../../node_modules/xslt-polyfill',
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate.'/xslt-polyfill.min.js')) {
                return $candidate;
            }
        }

        return null;
    }
}
