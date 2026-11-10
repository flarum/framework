<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Features;

use InvalidArgumentException;

/**
 * The features extensions have registered.
 *
 * @internal
 */
class FeatureRegistry
{
    /**
     * @var array<string, Feature>
     */
    protected array $features = [];

    /**
     * @throws InvalidArgumentException when another extension registered the same key
     */
    public function add(Feature $feature): void
    {
        if (isset($this->features[$feature->key])) {
            $first = $this->features[$feature->key]->extension ?? 'an unknown extension';
            $second = $feature->extension ?? 'an unknown extension';

            throw new InvalidArgumentException("The AI feature \"$feature->key\" is registered twice: by $first and by $second.");
        }

        $this->features[$feature->key] = $feature;
    }

    public function has(string $key): bool
    {
        return isset($this->features[$key]);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function get(string $key): Feature
    {
        return $this->features[$key] ?? throw new InvalidArgumentException("No AI feature called \"$key\" is registered.");
    }

    /**
     * @return array<string, Feature>
     */
    public function all(): array
    {
        return $this->features;
    }
}
