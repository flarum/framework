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
     * @param  \Illuminate\Http\Request $request
     * @return void
     */
    public function render($request, Throwable $e) /** @phpstan-ignore-line */
    {
        // TODO: Implement render() method.
    }

    /**
     * Render an exception to the console.
     *
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    public function renderForConsole($output, Throwable $e)
    {
        // TODO: Implement renderForConsole() method.
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
