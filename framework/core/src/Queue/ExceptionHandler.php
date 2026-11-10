<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue;

use Illuminate\Contracts\Debug\ExceptionHandler as ExceptionHandling;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

readonly class ExceptionHandler implements ExceptionHandling
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    /**
     * Report or log an exception.
     *
     * @return void
     */
    public function report(Throwable $e)
    {
        $this->logger->error((string) $e);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * Not applicable in a queue context — re-throw so the worker can handle it.
     */
    public function render($request, Throwable $e): never
    {
        throw $e;
    }

    /**
     * Render an exception to the console.
     *
     * @param OutputInterface $output
     */
    public function renderForConsole($output, Throwable $e): void
    {
        $output->writeln((string) $e);
    }

    /**
     * Determine if the exception should be reported.
     *
     * @return bool
     */
    public function shouldReport(Throwable $e)
    {
        return true;
    }
}
