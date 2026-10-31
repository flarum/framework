<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;

class ServiceWorker implements ExtenderInterface
{
    private array $files = [];

    /**
     * Adds a script to the forum's service worker, for example to show push
     * notifications. A browser keeps only one service worker for the forum,
     * so core and every extension share it. An extension that registers a
     * worker of its own at the forum's scope is replaced by core's whenever
     * core's is on, so it should add its script here instead.
     *
     * The script runs after core's, in a function of its own, so it can't be a
     * module. Core's answers page loads, to show an offline page when they
     * fail, so a script shouldn't answer them too.
     *
     * ```php
     * (new Extend\ServiceWorker())
     *   ->js(__DIR__.'/js/dist/sw.js')
     * ```
     *
     * @param string $file Absolute path of a JavaScript file.
     */
    public function js(string $file): self
    {
        $this->files[] = $file;

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $owner = $extension?->getId();

        $container->extend('flarum.forum.service_worker', function (array $parts) use ($owner) {
            return array_merge($parts, array_map(fn (string $file) => ['file' => $file, 'owner' => $owner], $this->files));
        });
    }
}
