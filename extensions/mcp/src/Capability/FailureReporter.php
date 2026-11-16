<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Capability;

use Flarum\Foundation\ErrorHandling\Reporter;
use Illuminate\Contracts\Container\Container;
use Throwable;

/**
 * Runs code an extension contributed (a capability's metadata, a schema
 * extender, an instruction callable) so that a failure is reported to the
 * forum's error reporters instead of taking the whole MCP endpoint down.
 *
 * @internal
 */
class FailureReporter
{
    public function __construct(
        protected Container $container,
    ) {
    }

    /**
     * @template T
     * @param callable(): T $call
     * @param T $fallback returned when the call throws
     * @return T
     */
    public function attempt(callable $call, mixed $fallback = null): mixed
    {
        try {
            return $call();
        } catch (Throwable $e) {
            $this->report($e);

            return $fallback;
        }
    }

    public function report(Throwable $e): void
    {
        foreach ($this->container->tagged(Reporter::class) as $reporter) {
            /** @var Reporter $reporter */
            $reporter->report($e);
        }
    }
}
