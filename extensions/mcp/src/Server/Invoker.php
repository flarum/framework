<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Server;

use Closure;
use Flarum\Foundation\ErrorHandling\Reporter;
use RuntimeException;
use Throwable;

/**
 * Runs capability code and sorts what it throws: errors the model can act on
 * go through the given factory, anything else is reported and replaced by a
 * generic error, so no stack trace or internal message reaches the client.
 */
class Invoker
{
    /**
     * @param iterable<Reporter> $reporters
     */
    public function __construct(
        protected ErrorTranslator $errors,
        protected iterable $reporters,
    ) {
    }

    /**
     * @template T
     * @param Closure(): T $call
     * @param Closure(string): Throwable $failure builds the exception the SDK turns into the model-facing error
     * @return T
     */
    public function run(Closure $call, Closure $failure): mixed
    {
        try {
            return $call();
        } catch (Throwable $e) {
            $message = $this->errors->translate($e);

            if ($message === null) {
                $this->report($e);

                throw new RuntimeException('Internal error');
            }

            throw $failure($message);
        }
    }

    public function report(Throwable $e): void
    {
        foreach ($this->reporters as $reporter) {
            $reporter->report($e);
        }
    }
}
