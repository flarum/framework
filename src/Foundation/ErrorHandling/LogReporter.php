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
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function report(Throwable $error)
    {
        $this->logger->error($error);
    }
}
