<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extend;

use Flarum\Extension\Extension;
use Flarum\Foundation\ErrorHandling\Reporter;
use Illuminate\Contracts\Container\Container;

class ErrorHandling implements ExtenderInterface
{
    private $statuses = [];
    private $types = [];
    private $handlers = [];
    private $reporters = [];

    /**
     * Define the corresponding HTTP status code for a well-known error type.
     *
     * This can be used to configure the status code (second parameter) to be
     * used for the error response when encountering an exception with a certain
     * type (first parameter). This type can be provided by the exception class
     * itself (if it implements {@see \Flarum\Foundation\KnownError}), or
     * explicitly defined by using the {@see type} method (useful for exception
     * classes not under your control).
     *
     * @param string $errorType: Type of the error.
     * @param int $httpStatus: The status code for this error.
     * @return self
     */
    public function status(string $errorType, int $httpStatus): self
    {
        $this->statuses[$errorType] = $httpStatus;

        return $this;
    }

    /**
     * Define the internal error type for a specific exception class.
     *
     * If the exception class is under your control, you should prefer having
     * the exception implement our {@see \Flarum\Foundation\KnownError}
     * interface and define the type there. This method should only be used for
     * third-party exceptions, e.g. when integrating another package that
     * already defines its own exception classes.
     *
     * @param string $exceptionClass: The ::class attribute of the exception class.
     * @param string $errorType: Type of the error.
     * @return self
     */
    public function type(string $exceptionClass, string $errorType): self
    {
        $this->types[$exceptionClass] = $errorType;

        return $this;
    }

    /**
     * Register a handler with custom error handling logic.
     *
     * When Flarum's default error handling is not enough for you, and the other
     * methods of this extender don't help, this is the place where you can go
     * wild! Using this method, you can define a handler class (second
     * parameter) that will be responsible for exceptions of a certain type
     * (first parameter).
     *
     * The handler class must implement a handle() method (surprise!), which
     * returns a {@see \Flarum\Foundation\ErrorHandling\HandledError} instance.
     * Besides the usual type and HTTP status code, such an object can also
     * contain "details" - arbitrary data with more context for to the error.
     *
     * @param string $exceptionClass: The ::class attribute of the exception class.
     * @param string $errorType: The ::class attribute of the handler class.
     * @return self
     */
    public function handler(string $exceptionClass, string $handlerClass): self
    {
        $this->handlers[$exceptionClass] = $handlerClass;

        return $this;
    }

    /**
     * Register an error reporter.
     *
     * Reporters will be called whenever Flarum encounters an exception that it
     * does not know how to handle (i.e. none of the well-known exceptions that
     * have an associated error type). They can then e.g. write the exception to
     * a log, or send it to some external service, so that developers and/or
     * administrators are notified about the error.
     *
     * When passing in a reporter class, make sure that it implements the
     * {@see \Flarum\Foundation\ErrorHandling\Reporter} interface.
     *
     * @param string $reporterClass: The ::class attribute of the reporter class.
     * @return self
     */
    public function reporter(string $reporterClass): self
    {
        $this->reporters[] = $reporterClass;

        return $this;
    }

    public function extend(Container $container, Extension $extension = null)
    {
        if (count($this->statuses)) {
            $container->extend('flarum.error.statuses', function ($statuses) {
                return array_merge($statuses, $this->statuses);
            });
        }

        if (count($this->types)) {
            $container->extend('flarum.error.classes', function ($types) {
                return array_merge($types, $this->types);
            });
        }

        if (count($this->handlers)) {
            $container->extend('flarum.error.handlers', function ($handlers) {
                return array_merge($handlers, $this->handlers);
            });
        }

        foreach ($this->reporters as $reporterClass) {
            $container->tag($reporterClass, Reporter::class);
        }
    }
}
