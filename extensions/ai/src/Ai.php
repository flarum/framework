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
use Flarum\User\User;
use Illuminate\Contracts\Queue\Queue;

/**
 * Where an extension's AI calls start:
 *
 *     resolve(Ai::class)->feature('acme-tldr.summaries')->as($actor)->prompt($agent, 'Summarise this.');
 *
 * Register the feature first, with {@see \Flarum\Ai\Extend\Ai::feature()}.
 * In the forum's frontend, `app.forum.attribute('aiFeatures')` lists the
 * features the current member or guest can use.
 */
class Ai
{
    public function __construct(
        protected FeatureRegistry $features,
        protected FeatureSettings $settings,
        protected AiConfig $config,
        protected Attribution $attribution,
        protected Queue $queue
    ) {
    }

    /**
     * @throws \InvalidArgumentException when no feature by that key is registered
     */
    public function feature(string $key): FeatureCall
    {
        return new FeatureCall($this->features->get($key), $this->settings, $this->config, $this->attribution, $this->queue);
    }

    /**
     * The keys of the features a member or guest could use now.
     *
     * @return string[]
     */
    public function availableTo(User $actor): array
    {
        return array_values(array_filter(
            array_keys($this->features->all()),
            fn (string $key) => $this->feature($key)->as($actor)->available()
        ));
    }
}
