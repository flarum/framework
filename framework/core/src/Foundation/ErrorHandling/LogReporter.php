<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling;

use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Log caught exceptions to a PSR-3 logger instance.
 */
class LogReporter implements Reporter
{
    public function __construct(
        protected LoggerInterface $logger
    ) {
    }

    public function report(Throwable $error): void
    {
        $this->logger->error($error);
    }
}
