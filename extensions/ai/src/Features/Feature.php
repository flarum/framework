<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Features;

/**
 * Something an extension uses AI for, registered with
 * {@see \Flarum\Ai\Extend\Ai::feature()}. The admin turns it on, chooses its
 * connection and model, limits its tokens and decides who may use it.
 */
class Feature
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly ?string $description,
        public readonly ?string $extension
    ) {
    }

    /**
     * The permission a member needs to use the feature.
     */
    public function permission(): string
    {
        return 'ai.feature.'.$this->key;
    }
}
