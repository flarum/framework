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
use Illuminate\Contracts\Container\Container;
use s9e\TextFormatter\Configurator;

class Formatter implements ExtenderInterface, LifecycleInterface
{
    private $configurationCallbacks = [];
    private $parsingCallbacks = [];
    private $renderingCallbacks = [];

    public function configure($callback)
    {
        $this->configurationCallbacks[] = $callback;

        return $this;
    }

    public function parse($callback)
    {
        $this->parsingCallbacks[] = $callback;

        return $this;
    }

    public function render($callback)
    {
        $this->renderingCallbacks[] = $callback;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        $container->extend('flarum.formatter', function ($formatter, $container) {
            foreach ($this->configurationCallbacks as $callback) {
                if (is_string($callback)) {
                    $callback = $container->make($callback);
                }

                $formatter->addConfigurationCallback($callback);
            }

            foreach ($this->parsingCallbacks as $callback) {
                if (is_string($callback)) {
                    $callback = $container->make($callback);
                }

                $formatter->addParsingCallback($callback);
            }

            foreach ($this->renderingCallbacks as $callback) {
                if (is_string($callback)) {
                    $callback = $container->make($callback);
                }

                $formatter->addRenderingCallback($callback);
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
