<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Formatter\Formatter as ActualFormatter;
use Flarum\Foundation\ContainerUtil;
use Illuminate\Contracts\Container\Container;

class Formatter implements ExtenderInterface, LifecycleInterface
{
    private $configurationCallbacks = [];
    private $parsingCallbacks = [];
    private $renderingCallbacks = [];

    /**
     * Configure the formatter. This can be used to add support for custom markdown/bbcode/etc tags,
     * or otherwise change the formatter. Please see documentation for the s9e text formatter library for more
     * information on how to use this.
     *
     * @param callable|string $callback
     *
     * The callback can be a closure or invokable class, and should accept:
     * - \s9e\TextFormatter\Configurator $configurator
     */
    public function configure($callback)
    {
        $this->configurationCallbacks[] = $callback;

        return $this;
    }

    /**
     * Prepare the system for parsing. This can be used to modify the text that will be parsed, or to modify the parser.
     * Please note that the text to be parsed must be returned, regardless of whether it's changed.
     *
     * @param callable|string $callback
     *
     * The callback can be a closure or invokable class, and should accept:
     * - \s9e\TextFormatter\Parser $parser
     * - mixed $context
     * - string $text: The text to be parsed.
     *
     * The callback should return:
     * - string $text: The text to be parsed.
     */
    public function parse($callback)
    {
        $this->parsingCallbacks[] = $callback;

        return $this;
    }

    /**
     * Prepare the system for rendering. This can be used to modify the xml that will be rendered, or to modify the renderer.
     * Please note that the xml to be rendered must be returned, regardless of whether it's changed.
     *
     * @param callable|string $callback
     *
     * The callback can be a closure or invokable class, and should accept:
     * - \s9e\TextFormatter\Rendered $renderer
     * - mixed $context
     * - string $xml: The xml to be rendered.
     * - ServerRequestInterface $request. This argument MUST either be nullable, or omitted entirely.
     *
     * The callback should return:
     * - string $xml: The xml to be rendered.
     */
    public function render($callback)
    {
        $this->renderingCallbacks[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.formatter', function ($formatter, $container) {
            foreach ($this->configurationCallbacks as $callback) {
                $formatter->addConfigurationCallback(ContainerUtil::wrapCallback($callback, $container));
            }

            foreach ($this->parsingCallbacks as $callback) {
                $formatter->addParsingCallback(ContainerUtil::wrapCallback($callback, $container));
            }

            foreach ($this->renderingCallbacks as $callback) {
                $formatter->addRenderingCallback(ContainerUtil::wrapCallback($callback, $container));
            }

            return $formatter;
        });
    }

    public function onEnable(Container $container, Extension $extension)
    {
        // FLush the formatter cache when this extension is enabled
        $container->make(ActualFormatter::class)->flush();
    }

    public function onDisable(Container $container, Extension $extension)
    {
        // FLush the formatter cache when this extension is disabled
        $container->make(ActualFormatter::class)->flush();
    }
}
