<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Usage;

use Flarum\User\User;

/**
 * What the AI call being made right now is for: the feature, and the member
 * it was made for, if any. Set by {@see \Flarum\Ai\Features\FeatureCall} for
 * the duration of a call, and read by the listeners that enforce limits and
 * record usage. A call made outside any feature is attributed to neither.
 */
class Attribution
{
    /**
     * @var list<array{feature: string, actor: User|null}>
     */
    protected array $stack = [];

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function during(string $feature, ?User $actor, callable $callback): mixed
    {
        $this->stack[] = ['feature' => $feature, 'actor' => $actor];

        try {
            return $callback();
        } finally {
            array_pop($this->stack);
        }
    }

    public function feature(): ?string
    {
        return $this->current()['feature'] ?? null;
    }

    public function actor(): ?User
    {
        return $this->current()['actor'] ?? null;
    }

    /**
     * @return array{feature: string, actor: User|null}|null
     */
    protected function current(): ?array
    {
        return $this->stack === [] ? null : $this->stack[array_key_last($this->stack)];
    }
}
