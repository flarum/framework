<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Mail\Transport\LogTransport;
use Psr\Log\LoggerInterface;
use Swift_Transport;

class LogDriver implements DriverInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function availableSettings(): array
    {
        return [];
    }

    public function buildTransport(SettingsRepositoryInterface $settings): Swift_Transport
    {
        return new LogTransport($this->logger);
    }
}
