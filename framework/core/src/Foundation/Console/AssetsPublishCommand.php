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
use Flarum\Formatter\XsltPolyfill;
use Flarum\Foundation\Paths;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Filesystem\Filesystem;

class AssetsPublishCommand extends AbstractCommand
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Paths
     */
    protected $paths;

    /**
     * @param Container $container
     * @param Paths $paths
     */
    public function __construct(Container $container, Paths $paths)
    {
        $this->container = $container;
        $this->paths = $paths;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('assets:publish')
            ->setDescription('Publish core and extension assets.');
    }

    /**
     * {@inheritdoc}
     */
    protected function fire()
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
    }

    /**
     * Copies the xslt-polyfill bundle into the public assets disk so the
     * Formatter can hand the browser a public URL when native XSLT is
     * disabled. Both files are kept in their original relative layout
     * (root + ./dist) so the polyfill's currentScript-based wasm loader
     * keeps working.
     *
     * @param Cloud $target
     * @param Filesystem $local
     */
    private function publishXsltPolyfill(Cloud $target, Filesystem $local)
    {
        $sourceDir = XsltPolyfill::findSource();

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
}
