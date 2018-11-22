<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend\Content;

use Flarum\Foundation\Application;
use Flarum\Frontend\Compiler\CompilerInterface;
use Flarum\Frontend\Document;
use Psr\Http\Message\ServerRequestInterface as Request;

class Assets
{
    protected $app;

    /**
     * @var \Flarum\Frontend\Assets
     */
    protected $assets;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function forFrontend(string $name)
    {
        $this->assets = $this->app->make('flarum.assets.'.$name);

        return $this;
    }

    public function __invoke(Document $document, Request $request)
    {
        $locale = $request->getAttribute('locale');

        $compilers = [
            'js' => [$this->assets->makeJs(), $this->assets->makeLocaleJs($locale)],
            'css' => [$this->assets->makeCss(), $this->assets->makeLocaleCss($locale)]
        ];

        if ($this->app->inDebugMode()) {
            $this->commit(array_flatten($compilers));
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
