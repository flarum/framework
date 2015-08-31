<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Formatter;

use Illuminate\Contracts\Cache\Repository;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Unparser;
use Flarum\Events\FormatterConfigurator;
use Flarum\Events\FormatterParser;
use Flarum\Events\FormatterRenderer;
use Flarum\Core\Posts\CommentPost;

class Formatter
{
    protected $cache;

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    protected function getConfigurator()
    {
        $configurator = new Configurator;
        $configurator->rootRules->enableAutoLineBreaks();

        $configurator->rendering->engine = 'PHP';
        $configurator->rendering->engine->cacheDir = storage_path() . '/app';

        $configurator->Escaper;
        $configurator->Autoemail;
        $configurator->Autolink;
        $configurator->tags->onDuplicate('replace');

        event(new FormatterConfigurator($configurator));

        $dom = $configurator->tags['URL']->template->asDOM();

        foreach ($dom->getElementsByTagName('a') as $a) {
            $a->setAttribute('target', '_blank');
            $a->setAttribute('rel', 'nofollow');
        }

        $dom->saveChanges();

        return $configurator;
    }

    public function flush()
    {
        $this->cache->forget('flarum.formatter.parser');
        $this->cache->forget('flarum.formatter.renderer');
    }

    protected function getComponent($key)
    {
        $cacheKey = 'flarum.formatter.' . $key;

        return $this->cache->rememberForever($cacheKey, function () use ($key) {
            return $this->getConfigurator()->finalize()[$key];
        });
    }

    protected function getParser(CommentPost $post)
    {
        $parser = $this->getComponent('parser');
        $parser->registeredVars['post'] = $post;

        event(new FormatterParser($parser, $post));

        return $parser;
    }

    protected function getRenderer(CommentPost $post)
    {
        spl_autoload_register(function ($class) {
            if (file_exists($file = storage_path() . '/app/' . $class . '.php')) {
                include $file;
            }
        });

        $renderer = $this->getComponent('renderer');

        event(new FormatterRenderer($renderer, $post));

        return $renderer;
    }

    public function getJS()
    {
        $configurator = $this->getConfigurator();
        $configurator->enableJavaScript();
        $configurator->javascript->exportMethods = ['preview'];
        $minifier = $configurator->javascript->setMinifier('ClosureCompilerService');
        $minifier->keepGoing = true;
        $minifier->cacheDir = storage_path() . '/app';

        return $configurator->finalize([
            'returnParser'   => false,
            'returnRenderer' => false
        ])['js'];
    }

    public function parse($text, CommentPost $post)
    {
        $parser = $this->getParser($post);

        return $parser->parse($text);
    }

    public function render($xml, CommentPost $post)
    {
        $renderer = $this->getRenderer($post);

        return $renderer->render($xml);
    }

    public function unparse($xml)
    {
        return Unparser::unparse($xml);
    }
}
