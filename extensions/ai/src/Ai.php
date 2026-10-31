<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai;

use Flarum\Ai\Features\FeatureCall;
use Flarum\Ai\Features\FeatureRegistry;
use Flarum\Ai\Features\FeatureSettings;
use Flarum\Ai\Usage\Attribution;

/**
 * Where an extension's AI calls start:
 *
 *     resolve(Ai::class)->feature('acme-tldr.summaries')->as($actor)->prompt($agent, 'Summarise this.');
 *
 * Register the feature first, with {@see \Flarum\Ai\Extend\Ai::feature()}.
 */
class Ai
{
    public function __construct(
        protected FeatureRegistry $features,
        protected FeatureSettings $settings,
        protected AiConfig $config,
        protected Attribution $attribution
    ) {
    }

    /**
     * @throws \InvalidArgumentException when no feature by that key is registered
     */
    public function feature(string $key): FeatureCall
    {
        return new FeatureCall($this->features->get($key), $this->settings, $this->config, $this->attribution);
    }
}
