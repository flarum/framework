<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Formatter;

use Flarum\Formatter\Event\Configuring;
use Flarum\Formatter\Event\Parsing;
use Flarum\Formatter\Event\Rendering;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Http\Message\ServerRequestInterface;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Unparser;

class Formatter
{
    protected $configurationCallbacks = [];

    protected $parsingCallbacks = [];

    protected $renderingCallbacks = [];

    /**
     * @var Repository
     */
    protected $cache;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @param Repository $cache
     * @param Dispatcher $events
     * @param string $cacheDir
     */
    public function __construct(Repository $cache, Dispatcher $events, $cacheDir)
    {
        $this->cache = $cache;
        $this->events = $events;
        $this->cacheDir = $cacheDir;
    }

    public function addConfigurationCallback($callback)
    {
        $this->configurationCallbacks[] = $callback;
    }

    public function addParsingCallback($callback)
    {
        $this->parsingCallbacks[] = $callback;
    }

    public function addRenderingCallback($callback)
    {
        $this->renderingCallbacks[] = $callback;
    }

    /**
     * Parse text.
     *
     * @param string $text
     * @param mixed $context
     * @return string
     */
    public function parse($text, $context = null)
    {
        $parser = $this->getParser($context);

        // Deprecated in beta 15, remove in beta 16
        $this->events->dispatch(new Parsing($parser, $context, $text));

        foreach ($this->parsingCallbacks as $callback) {
            $text = $callback($parser, $context, $text);
        }

        return $parser->parse($text);
    }

    /**
     * Render parsed XML.
     *
     * @param string $xml
     * @param mixed $context
     * @param ServerRequestInterface|null $request
     * @return string
     */
    public function render($xml, $context = null, ServerRequestInterface $request = null)
    {
        $renderer = $this->getRenderer();

        // Deprecated in beta 15, remove in beta 16
        $this->events->dispatch(new Rendering($renderer, $context, $xml, $request));

        foreach ($this->renderingCallbacks as $callback) {
            $xml = $callback($renderer, $context, $xml, $request);
        }

        return $renderer->render($xml);
    }

    /**
     * Unparse XML.
     *
     * @param string $xml
     * @return string
     */
    public function unparse($xml)
    {
        return Unparser::unparse($xml);
    }

    /**
     * Flush the cache so that the formatter components are regenerated.
     */
    public function flush()
    {
        $this->cache->forget('flarum.formatter');
    }

    /**
     * @return Configurator
     */
    protected function getConfigurator()
    {
        $configurator = new Configurator;

        $configurator->rootRules->enableAutoLineBreaks();

        $configurator->rendering->engine = 'PHP';
        $configurator->rendering->engine->cacheDir = $this->cacheDir;

        $configurator->enableJavaScript();
        $configurator->javascript->exports = ['preview'];

        $configurator->javascript->setMinifier('MatthiasMullieMinify')
            ->keepGoing = true;

        $configurator->Escaper;
        $configurator->Autoemail;
        $configurator->Autolink;
        $configurator->tags->onDuplicate('replace');

        // Deprecated in beta 15, remove in beta 16
        $this->events->dispatch(new Configuring($configurator));

        foreach ($this->configurationCallbacks as $callback) {
            $callback($configurator);
        }

        $this->configureExternalLinks($configurator);

        return $configurator;
    }

    /**
     * @param Configurator $configurator
     */
    protected function configureExternalLinks(Configurator $configurator)
    {
        $dom = $configurator->tags['URL']->template->asDOM();

        foreach ($dom->getElementsByTagName('a') as $a) {
            $rel = $a->getAttribute('rel');
            $a->setAttribute('rel', "$rel nofollow ugc");
        }

        $dom->saveChanges();
    }

    /**
     * Get a TextFormatter component.
     *
     * @param string $name "renderer" or "parser" or "js"
     * @return mixed
     */
    protected function getComponent($name)
    {
        $formatter = $this->cache->rememberForever('flarum.formatter', function () {
            return $this->getConfigurator()->finalize();
        });

        return $formatter[$name];
    }

    /**
     * Get the parser.
     *
     * @param mixed $context
     * @return \s9e\TextFormatter\Parser
     */
    protected function getParser($context = null)
    {
        $parser = $this->getComponent('parser');

        $parser->registeredVars['context'] = $context;

        return $parser;
    }

    /**
     * Get the renderer.
     *
     * @return \s9e\TextFormatter\Renderer
     */
    protected function getRenderer()
    {
        spl_autoload_register(function ($class) {
            if (file_exists($file = $this->cacheDir.'/'.$class.'.php')) {
                include $file;
            }
        });

        return $this->getComponent('renderer');
    }

    /**
     * Get the formatter JavaScript.
     *
     * @return string
     */
    public function getJs()
    {
        return $this->getComponent('js');
    }
}
