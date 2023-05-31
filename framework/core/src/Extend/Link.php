<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Closure;
use Flarum\Extension\Extension;
use Flarum\Foundation\Config;
use Illuminate\Contracts\Container\Container;
use Laminas\Diactoros\Uri;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Utils;

class Link implements ExtenderInterface
{
    protected Closure|null $setRel = null;
    protected Closure|null $setTarget = null;

    public function setRel(Closure $callable): self
    {
        $this->setRel = $callable;

        return $this;
    }

    public function setTarget(Closure $callable): self
    {
        $this->setTarget = $callable;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null): void
    {
        $siteUrl = $container->make(Config::class)->url();

        (new Formatter)->render(function (Renderer $renderer, $context, string $xml) use ($siteUrl) {
            return Utils::replaceAttributes($xml, 'URL', function ($attributes) use ($siteUrl) {
                $uri = isset($attributes['url'])
                    ? new Uri($attributes['url'])
                    : null;

                $setRel = $this->setRel;
                if ($setRel && $rel = $setRel($uri, $siteUrl, $attributes)) {
                    $attributes['rel'] = $rel;
                }

                $setTarget = $this->setTarget;
                if ($setTarget && $target = $setTarget($uri, $siteUrl, $attributes)) {
                    $attributes['target'] = $target;
                }

                return $attributes;
            });
        })->extend($container);
    }
}
