<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai\Usage;

use WeakMap;

/**
 * What an AI call is for: the feature, and who it's made for. Set by
 * {@see \Flarum\Ai\Features\FeatureCall} and read by the listeners that
 * enforce limits and record usage.
 *
 * Two ways to find it, because not every call happens while the feature call
 * that started it is still running:
 * - Agents a feature call is given are remembered, so a stream read later, or
 *   a queued call run in a worker, is still counted for the right feature.
 * - Anything else, such as embeddings made inside `run()`, is counted for the
 *   feature call running at the time.
 *
 * A call made outside any feature is counted for neither.
 *
 * @internal
 */
class Attribution
{
    /**
     * @var list<Context>
     */
    protected array $stack = [];

    /**
     * @var WeakMap<object, Context>
     */
    protected WeakMap $agents;

    public function __construct()
    {
        $this->agents = new WeakMap();
    }

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function during(Context $context, callable $callback): mixed
    {
        $this->stack[] = $context;

        try {
            return $callback();
        } finally {
            array_pop($this->stack);
        }
    }

    public function bind(object $agent, Context $context): void
    {
        $this->agents[$agent] = $context;
    }

    /**
     * The context of a call: the one its agent was bound to, if it has an
     * agent, or else the feature call running now.
     */
    public function for(?object $agent = null): ?Context
    {
        if ($agent !== null && isset($this->agents[$agent])) {
            return $this->agents[$agent];
        }

        return $this->stack === [] ? null : $this->stack[array_key_last($this->stack)];
    }
}
