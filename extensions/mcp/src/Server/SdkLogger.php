<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Server;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Stringable;

/**
 * Flarum's logger for the SDK. The SDK logs every request at info level
 * ("Handling request.", ...), which Flarum's log keeps outside debug mode;
 * only warnings and worse get through then.
 *
 * @internal
 */
class SdkLogger extends AbstractLogger
{
    protected const array SEVERITY = [
        LogLevel::DEBUG => 0,
        LogLevel::INFO => 1,
        LogLevel::NOTICE => 2,
        LogLevel::WARNING => 3,
        LogLevel::ERROR => 4,
        LogLevel::CRITICAL => 5,
        LogLevel::ALERT => 6,
        LogLevel::EMERGENCY => 7,
    ];

    public function __construct(
        protected LoggerInterface $logger,
        protected bool $debug,
    ) {
    }

    /**
     * @param mixed $level
     * @param array<mixed> $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        if (! $this->debug && (static::SEVERITY[$level] ?? static::SEVERITY[LogLevel::WARNING]) < static::SEVERITY[LogLevel::WARNING]) {
            return;
        }

        $this->logger->log($level, '[mcp] '.$message, $context);
    }
}
