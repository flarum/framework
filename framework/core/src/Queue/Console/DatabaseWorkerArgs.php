<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Queue\Console;

use Flarum\Settings\SettingsRepositoryInterface;

class DatabaseWorkerArgs
{
    public function __construct(protected SettingsRepositoryInterface $settings)
    {
    }

    public function args(): array
    {
        $args = [
            '--stop-when-empty',
        ];

        if ($retries = $this->settings->get('database-queue.retries')) {
            $args['--tries'] = $retries;
        }

        if ($memory = $this->settings->get('database-queue.memory')) {
            $args['--memory'] = $memory;
        }

        if ($timeout = $this->settings->get('database-queue.timeout')) {
            $args['--timeout'] = $timeout;
        }

        if ($rest = $this->settings->get('database-queue.rest')) {
            $args['--rest'] = $rest;
        }

        if ($backoff = $this->settings->get('database-queue.backoff')) {
            $args['--backoff'] = $backoff;
        }

        return $args;
    }
}
