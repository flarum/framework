<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Extend;

use Flarum\Ai\Features\Feature;
use Flarum\Ai\Features\FeatureRegistry;
use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;

/**
 * Registers what an extension uses AI for:
 *
 *     (new \Flarum\Ai\Extend\Ai())
 *         ->feature('acme-tldr.summaries', 'acme-tldr.admin.ai.summaries_label', 'acme-tldr.admin.ai.summaries_help'),
 *
 * Each feature appears on the AI admin page, where the admin turns it on
 * (features start off), picks its connection and model, sets a daily token
 * limit, and grants the permission to use it, `ai.feature.{key}`, which no
 * group has to begin with. Make calls with {@see \Flarum\Ai\Ai::feature()}.
 */
class Ai implements ExtenderInterface
{
    /**
     * @var list<array{key: string, label: string, description: string|null}>
     */
    protected array $features = [];

    /**
     * @param string $key Your extension's ID and a name, in lower case, like "acme-tldr.summaries". At most 89 characters.
     * @param string $label A translation key for the feature's name.
     * @param string|null $description A translation key for a sentence on what it does.
     *
     * @throws \InvalidArgumentException when the key isn't valid
     */
    public function feature(string $key, string $label, ?string $description = null): self
    {
        Feature::assertValidKey($key);

        $this->features[] = compact('key', 'label', 'description');

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $container->extend(FeatureRegistry::class, function (FeatureRegistry $registry) use ($extension) {
            foreach ($this->features as $feature) {
                $registry->add(new Feature($feature['key'], $feature['label'], $feature['description'], $extension?->getId()));
            }

            return $registry;
        });
    }
}
