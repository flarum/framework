<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\MessageBag;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

class LogDriver implements DriverInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    public function availableSettings(): array
    {
        return [];
    }

    public function validate(SettingsRepositoryInterface $settings, Factory $validator): MessageBag
    {
        return new MessageBag;
    }

    public function canSend(): bool
    {
        return false;
    }

    public function buildTransport(SettingsRepositoryInterface $settings): TransportInterface
    {
        return new FlarumLogTransport($this->logger);
    }
}
