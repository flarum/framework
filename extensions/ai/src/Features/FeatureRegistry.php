<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Features;

use InvalidArgumentException;

class FeatureRegistry
{
    /**
     * @var array<string, Feature>
     */
    protected array $features = [];

    public function add(Feature $feature): void
    {
        if (! preg_match('/^[a-z0-9][a-z0-9_-]*(\.[a-z0-9_-]+)+$/', $feature->key) || strlen($feature->key) > 100) {
            throw new InvalidArgumentException("\"$feature->key\" is not a valid AI feature key. Use your extension's ID and a name, like \"acme-tldr.summaries\".");
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
