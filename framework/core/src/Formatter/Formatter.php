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
        protected string $cacheDir,
        protected ?string $xsltPolyfillUrl = null
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

    public function parse(string $text, mixed $context = null, ?User $user = null): string
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

    public function render(string $xml, mixed $context = null, ?ServerRequestInterface $request = null): string
    {
        $renderer = $this->getRenderer();

        foreach ($this->renderingCallbacks as $callback) {
            $xml = $callback($renderer, $context, $xml, $request);
        }

        $xml = $this->configureDefaultsOnLinks($xml);

        return $renderer->render($xml);
    }

    public function unparse(?string $xml, mixed $context = null): ?string
    {
        if ($xml === null) {
            return null;
        }

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
     *
     * If a polyfill URL is configured, prepends a small detector that
     * loads the xslt-polyfill (~510 KB gzipped) only on browsers where
     * native XSLT is unavailable. Chrome disabled XSLT by default in
     * Beta channel from version 145 (Dec 2025) and on Stable from
     * version 158 (Nov 2026). The polyfill is a temporary measure
     * pending an upstream s9e fix that removes the XSLT dependency.
     *
     * @see https://github.com/s9e/TextFormatter/issues/250
     */
    public function getJs(): string
    {
        $s9eJs = $this->getComponent('js');

        if ($this->xsltPolyfillUrl === null) {
            return $s9eJs;
        }

        $url = json_encode($this->xsltPolyfillUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        return <<<JS
(function() {
    try { if (typeof XSLTProcessor !== 'undefined' && new XSLTProcessor()) return; } catch (e) {}
    var s = document.createElement('script');
    s.src = $url;
    s.async = false;
    document.head.appendChild(s);
})();
$s9eJs
JS;
    }

    protected function configureDefaultsOnLinks(string $xml): string
    {
        return Utils::replaceAttributes($xml, 'URL', function ($attributes) {
            $attributes['rel'] ??= 'ugc nofollow';

            return $attributes;
        });
    }

    /**
     * Converts a plain text string (with or without Markdown) to it's HTML equivalent.
     */
    public function convert(?string $content): string
    {
        if (! $content) {
            return '';
        }

        return $this->getRenderer()->render($this->getParser()->parse($content));
    }
}
