<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Formatter;

use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository;
use Psr\Http\Message\ServerRequestInterface;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Unparser;
use s9e\TextFormatter\Utils;

class Formatter
{
    protected $configurationCallbacks = [];

    protected $parsingCallbacks = [];

    protected $unparsingCallbacks = [];

    protected $renderingCallbacks = [];

    /**
     * @var Repository
     */
    protected $cache;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @param Repository $cache
     * @param string $cacheDir
     */
    public function __construct(Repository $cache, $cacheDir)
    {
        $this->cache = $cache;
        $this->cacheDir = $cacheDir;
    }

    /**
     * @internal
     */
    public function addConfigurationCallback($callback)
    {
        $this->configurationCallbacks[] = $callback;
    }

    /**
     * @internal
     */
    public function addParsingCallback($callback)
    {
        $this->parsingCallbacks[] = $callback;
    }

    /**
     * @internal
     */
    public function addUnparsingCallback($callback)
    {
        $this->unparsingCallbacks[] = $callback;
    }

    /**
     * @internal
     */
    public function addRenderingCallback($callback)
    {
        $this->renderingCallbacks[] = $callback;
    }

    /**
     * Parse text.
     *
     * @param string $text
     * @param mixed $context
     * @param User|null $user
     * @return string the parsed XML
     */
    public function parse($text, $context = null, User $user = null)
    {
        $parser = $this->getParser($context);

        /*
         * Can be injected in tag or attribute filters by calling:
         * ->addParameterByName('actor') on the filter.
         * See the mentions extension's ConfigureMentions.php for an example.
         */
        $parser->registeredVars['actor'] = $user;

        foreach ($this->parsingCallbacks as $callback) {
            $text = $callback($parser, $context, $text, $user);
        }

        return $parser->parse($text);
    }

    /**
     * Render parsed XML.
     *
     * @param string $xml
     * @param mixed|null $context
     * @param ServerRequestInterface|null $request
     * @return string
     */
    public function render($xml, $context = null, ServerRequestInterface $request = null)
    {
        $renderer = $this->getRenderer();

        foreach ($this->renderingCallbacks as $callback) {
            $xml = $callback($renderer, $context, $xml, $request);
        }

        $xml = $this->configureDefaultsOnLinks($renderer, $xml, $context, $request);

        return $renderer->render($xml);
    }

    /**
     * Unparse XML.
     *
     * @param string $xml
     * @param mixed $context
     * @return string
     */
    public function unparse($xml, $context = null)
    {
        foreach ($this->unparsingCallbacks as $callback) {
            $xml = $callback($context, $xml);
        }

        return $xml !== null ? Unparser::unparse($xml) : null;
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

        $configurator->rendering->setEngine('PHP');
        $configurator->rendering->getEngine()->cacheDir = $this->cacheDir; // @phpstan-ignore-line

        $configurator->enableJavaScript();
        $configurator->javascript->exports = ['preview'];

        $configurator->javascript->setMinifier('MatthiasMullieMinify')
            ->keepGoing = true;

        $configurator->Escaper; /** @phpstan-ignore-line */
        $configurator->Autoemail; /** @phpstan-ignore-line */
        $configurator->Autolink; /** @phpstan-ignore-line */
        $configurator->tags->onDuplicate('replace');

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
        /**
         * @var Configurator\Items\TemplateDocument $dom
         */
        $dom = $configurator->tags['URL']->template->asDOM();

        foreach ($dom->getElementsByTagName('a') as $a) {
            /** @var \s9e\SweetDOM\Element $a */
            $a->prependXslCopyOf('@target');
            $a->prependXslCopyOf('@rel');
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
     * @return Renderer
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

    protected function configureDefaultsOnLinks(
        Renderer $renderer,
        string $xml,
        $context = null,
        ServerRequestInterface $request = null
    ): string {
        return Utils::replaceAttributes($xml, 'URL', function ($attributes) {
            $attributes['rel'] = $attributes['rel'] ?? 'ugc nofollow';

            return $attributes;
        });
    }
}
