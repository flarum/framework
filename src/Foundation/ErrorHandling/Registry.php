<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling;

use Flarum\Foundation\KnownError;
use Throwable;

/**
 * Flarum's central registry of known error types.
 *
 * It knows how to deal with errors raised both within Flarum's core and outside
 * of it, map them to error "types" and how to determine appropriate HTTP status
 * codes for them.
 */
class Registry
{
    private $statusMap;
    private $classMap;
    private $handlerMap;

    public function __construct(array $statusMap, array $classMap, array $handlerMap)
    {
        $this->statusMap = $statusMap;
        $this->classMap = $classMap;
        $this->handlerMap = $handlerMap;
    }

    /**
     * Map exceptions to handled errors.
     *
     * This can map internal ({@see \Flarum\Foundation\KnownError}) as well as
     * external exceptions (any classes inheriting from \Throwable) to instances
     * of {@see \Flarum\Foundation\ErrorHandling\HandledError}.
     *
     * Even for unknown exceptions, a generic fallback will always be returned.
     *
     * @param Throwable $error
     * @return HandledError
     */
    public function handle(Throwable $error): HandledError
    {
        return $this->handleKnownTypes($error)
            ?? $this->handleCustomTypes($error)
            ?? HandledError::unknown($error);
    }

    private function handleKnownTypes(Throwable $error): ?HandledError
    {
        $errorType = null;

        if ($error instanceof KnownError) {
            $errorType = $error->getType();
        } else {
            $errorClass = get_class($error);
            if (isset($this->classMap[$errorClass])) {
                $errorType = $this->classMap[$errorClass];
            }
        }

        if ($errorType) {
            return new HandledError(
                $error,
                $errorType,
                $this->statusMap[$errorType] ?? 500
            );
        }

        return null;
    }

    private function handleCustomTypes(Throwable $error): ?HandledError
    {
        $errorClass = get_class($error);

        if (isset($this->handlerMap[$errorClass])) {
            $handler = new $this->handlerMap[$errorClass];

            return $handler->handle($error);
        }

        return null;
    }
}
