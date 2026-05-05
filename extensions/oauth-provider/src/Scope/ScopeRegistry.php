<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\OAuthProvider\Scope;

class ScopeRegistry
{
    /**
     * @var array<string, string> Identifier => human-readable description
     */
    protected array $scopes = [];

    public function register(string $identifier, string $description): self
    {
        $this->scopes[$identifier] = $description;

        return $this;
    }

    public function has(string $identifier): bool
    {
        return array_key_exists($identifier, $this->scopes);
    }

    public function description(string $identifier): ?string
    {
        return $this->scopes[$identifier] ?? null;
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        return $this->scopes;
    }
}
