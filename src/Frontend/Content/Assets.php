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
    protected $container;
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

    public function forFrontend(string $name)
    {
        $this->assets = $this->container->make('flarum.assets.'.$name);

        return $this;
    }

    public function __invoke(Document $document, Request $request)
    {
        $locale = $request->getAttribute('locale');

        $compilers = [
            'js' => [$this->assets->makeJs(), $this->assets->makeLocaleJs($locale)],
            'css' => [$this->assets->makeCss(), $this->assets->makeLocaleCss($locale)]
        ];

        if ($this->config->inDebugMode()) {
            $this->commit(Arr::flatten($compilers));
        }

        $document->js = array_merge($document->js, $this->getUrls($compilers['js']));
        $document->css = array_merge($document->css, $this->getUrls($compilers['css']));
    }

    private function commit(array $compilers)
    {
        foreach ($compilers as $compiler) {
            $compiler->commit();
        }
    }

    /**
     * @param CompilerInterface[] $compilers
     * @return string[]
     */
    private function getUrls(array $compilers)
    {
        return array_filter(array_map(function (CompilerInterface $compiler) {
            return $compiler->getUrl();
        }, $compilers));
    }
}
