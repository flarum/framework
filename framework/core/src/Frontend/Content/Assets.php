<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Content;

use Flarum\Foundation\Config;
use Flarum\Locale\LocaleManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\Frontend\Assets as FrontendAssets;
use Flarum\Frontend\Compiler\CompilerInterface;
use Flarum\Frontend\Document;
use Flarum\Frontend\RecompileFrontendAssets;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class Assets
{
    protected FrontendAssets $assets;
    protected FrontendAssets $commonAssets;

    public function __construct(
        protected Container $container,
        protected Config $config
    ) {
    }

    /**
     * Sets the frontend to generate assets for.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function forFrontend(string $name): self
    {
        $this->assets = $this->container->make('flarum.assets.'.$name);
        $this->commonAssets = $this->container->make('flarum.assets.common');

        return $this;
    }

    public function __invoke(Document $document, Request $request): void
    {
        $locale = $request->getAttribute('locale');

        // Rebuild any asset set that was flagged dirty (e.g. by an extension
        // toggle). This runs here — in a freshly-booted request that reflects
        // the current extension state — rather than in the toggling request,
        // whose container predates the toggle and would bake stale sources
        // (e.g. locale bundles without a newly-enabled extension's keys) into
        // the manifest. The rebuild is in place: nothing is deleted, so
        // already-served URLs keep resolving and the asset revision token only
        // moves if the output genuinely changed.
        $this->recompileIfDirty($this->assets);
        $this->recompileIfDirty($this->commonAssets);

        $compilers = $this->assembleCompilers($locale);

        if ($this->config->inDebugMode()) {
            $this->forceCommit(Arr::flatten($compilers));
        }

        $this->addAssetsToDocument($document, $compilers);
    }

    protected function recompileIfDirty(FrontendAssets $assets): void
    {
        (new RecompileFrontendAssets(
            $assets,
            $this->container->make(LocaleManager::class),
            $this->container->make('events'),
            $this->container->make(SettingsRepositoryInterface::class)
        ))->recompileIfDirty();
    }

    /**
     * Assembles JS and CSS compilers to be used to generate frontend assets.
     *
     * @return array[]
     */
    protected function assembleCompilers(?string $locale): array
    {
        $frontendCompilers = [
            'js' => [$this->assets->makeJs(), $this->assets->makeLocaleJs($locale), $this->assets->makeJsDirectory()],
            'css' => [$this->assets->makeCss(), $this->assets->makeLocaleCss($locale)]
        ];

        $commonCompilers = [
            'js' => [$this->commonAssets->makeJsDirectory()],
        ];

        return array_merge_recursive($commonCompilers, $frontendCompilers);
    }

    /**
     * Adds URLs of frontend JS and CSS to the {@link Document} class.
     */
    protected function addAssetsToDocument(Document $document, array $compilers): void
    {
        $document->js = array_merge($document->js, $this->getUrls($compilers['js']));
        $document->css = array_merge($document->css, $this->getUrls($compilers['css']));
    }

    /**
     * Force compilation of assets when in debug mode.
     *
     * @param CompilerInterface[] $compilers
     */
    protected function forceCommit(array $compilers): void
    {
        foreach ($compilers as $compiler) {
            $compiler->commit(true);
        }
    }

    /**
     * Maps provided {@link CompilerInterface}s to their URLs.
     *
     * @param CompilerInterface[] $compilers
     * @return string[]
     */
    protected function getUrls(array $compilers): array
    {
        return array_filter(array_map(function (CompilerInterface $compiler) {
            return $compiler->getUrl();
        }, $compilers));
    }
}
