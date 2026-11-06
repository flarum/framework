<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Models;

use JsonSerializable;

/**
 * A model a connection's provider offers, as its API lists it.
 */
class ProviderModel implements JsonSerializable
{
    public function __construct(
        /** What a call names the model by. */
        public readonly string $id,
        /** The provider's name for it, where it gives one. */
        public readonly ?string $name = null,
        /** When the provider deprecated it, in ISO 8601, if it says. */
        public readonly ?string $deprecatedAt = null,
        /** When the provider stops serving it, in ISO 8601, if it says. */
        public readonly ?string $retiresAt = null
    ) {
    }

    /**
     * Whether the provider has said it's going away.
     */
    public function isDeprecated(): bool
    {
        return $this->deprecatedAt !== null || $this->retiresAt !== null;
    }

    /**
     * @return array{id: string, name: string|null, deprecatedAt: string|null, retiresAt: string|null}
     */
    public function jsonSerialize(): array
    {
        return ['id' => $this->id, 'name' => $this->name, 'deprecatedAt' => $this->deprecatedAt, 'retiresAt' => $this->retiresAt];
    }

    /**
     * @param array{id: string, name?: string|null, deprecatedAt?: string|null, retiresAt?: string|null} $data
     */
    public static function fromArray(array $data): self
    {
        return new self($data['id'], $data['name'] ?? null, $data['deprecatedAt'] ?? null, $data['retiresAt'] ?? null);
    }
}
