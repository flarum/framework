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
use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Unparser;
use s9e\TextFormatter\Utils;

class Formatter
{
    protected array $configurationCallbacks = [];
    protected array $parsingCallbacks = [];
    protected array $unparsingCallbacks = [];
    protected array $renderingCallbacks = [];

    public function __construct(
        protected Repository $cache,
        protected string $cacheDir
    ) {
    }

    /**
     * @internal
     */
    public function addConfigurationCallback(callable $callback): void
    {
        $this->configurationCallbacks[] = $callback;
    }

    /**
     * @internal
     */
    public function addParsingCallback(callable $callback): void
    {
        $this->parsingCallbacks[] = $callback;
    }

    /**
     * @internal
     */
    public function addUnparsingCallback(callable $callback): void
    {
        $this->unparsingCallbacks[] = $callback;
    }

    /**
     * @internal
     */
    public function addRenderingCallback(callable $callback): void
    {
        $this->renderingCallbacks[] = $callback;
    }

    public function parse(string $text, mixed $context = null, User $user = null): string
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

    public function render(string $xml, mixed $context = null, ServerRequestInterface $request = null): string
    {
        $renderer = $this->getRenderer();

        foreach ($this->renderingCallbacks as $callback) {
            $xml = $callback($renderer, $context, $xml, $request);
        }

        $xml = $this->configureDefaultsOnLinks($xml);

        return $renderer->render($xml);
    }

    public function unparse(string $xml, $context = null): string
    {
        foreach ($this->unparsingCallbacks as $callback) {
            $xml = $callback($context, $xml);
        }

        return Unparser::unparse($xml);
    }

    /**
     * Flush the cache so that the formatter components are regenerated.
     */
    public function flush(): void
    {
        $this->cache->forget('flarum.formatter');
    }

    protected function getConfigurator(): Configurator
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

    protected function configureExternalLinks(Configurator $configurator): void
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
     * Get a TextFormatter component ("renderer" or "parser" or "js").
     */
    protected function getComponent(string $name): mixed
    {
        $formatter = $this->cache->rememberForever('flarum.formatter', function () {
            return $this->getConfigurator()->finalize();
        });

        return $formatter[$name];
    }

    protected function getParser(mixed $context = null): Parser
    {
        $parser = $this->getComponent('parser');

        $parser->registeredVars['context'] = $context;

        return $parser;
    }

    protected function getRenderer(): Renderer
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
     */
    public function getJs(): string
    {
        return $this->getComponent('js');
    }

    protected function configureDefaultsOnLinks(string $xml): string
    {
        return Utils::replaceAttributes($xml, 'URL', function ($attributes) {
            $attributes['rel'] = $attributes['rel'] ?? 'ugc nofollow';

            return $attributes;
        });
    }
}
