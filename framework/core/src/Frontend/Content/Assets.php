<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Content;

use Flarum\Foundation\Config;
use Flarum\Frontend\Compiler\CompilerInterface;
use Flarum\Frontend\Document;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class Assets
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Flarum\Frontend\Assets
     */
    protected $assets;

    public function __construct(Container $container, Config $config)
    {
        $this->container = $container;
        $this->config = $config;
    }

    /**
     * Sets the frontend to generate assets for.
     *
     * @param string $name frontend name
     * @return $this
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function forFrontend(string $name): Assets
    {
        $this->assets = $this->container->make('flarum.assets.'.$name);

        return $this;
    }

    public function __invoke(Document $document, Request $request)
    {
        $locale = $request->getAttribute('locale');

        $compilers = $this->assembleCompilers($locale);

        if ($this->config->inDebugMode()) {
            $this->forceCommit(Arr::flatten($compilers));
        }

        $this->addAssetsToDocument($document, $compilers);
    }

    /**
     * Assembles JS and CSS compilers to be used to generate frontend assets.
     *
     * @param string|null $locale
     * @return array[]
     */
    protected function assembleCompilers(?string $locale): array
    {
        return [
            'js' => [$this->assets->makeJs(), $this->assets->makeLocaleJs($locale)],
            'css' => [$this->assets->makeCss(), $this->assets->makeLocaleCss($locale)]
        ];
    }

    /**
     * Adds URLs of frontend JS and CSS to the {@link Document} class.
     *
     * @param Document $document
     * @param array $compilers
     * @return void
     */
    protected function addAssetsToDocument(Document $document, array $compilers): void
    {
        $document->js = array_merge($document->js, $this->getUrls($compilers['js']));
        $document->css = array_merge($document->css, $this->getUrls($compilers['css']));
    }

    /**
     * Force compilation of assets when in debug mode.
     *
     * @param array $compilers
     */
    protected function forceCommit(array $compilers): void
    {
        /** @var CompilerInterface $compiler */
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
