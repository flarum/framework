<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Realtime\Websocket\Logger;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

class Logger
{
    /**
     * Wether the logger is enabled.
     *
     * @var bool
     */
    protected $enabled = false;

    /**
     * Wether the verbose mode is on.
     *
     * @var bool
     */
    protected $verbose = false;

    /**
     * Create a new Logger instance.
     */
    public function __construct(protected OutputInterface $consoleOutput)
    {
    }

    public static function isEnabled(): bool
    {
        $logger = app(WebsocketLogger::class);

        return $logger->enabled;
    }

    /**
     * Enable the logger.
     *
     * @param  bool  $enabled
     * @return $this
     */
    public function enable($enabled = true)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Enable the verbose mode.
     *
     * @param  bool  $verbose
     * @return $this
     */
    public function verbose($verbose = false)
    {
        $this->verbose = $verbose;

        return $this;
    }

    /**
     * Trigger an Info message.
     *
     * @param  string  $message
     * @return void
     */
    protected function info(string $message)
    {
        $this->line($message, 'info');
    }

    /**
     * Trigger a Warning message.
     *
     * @param  string  $message
     * @return void
     */
    protected function warn(string $message)
    {
        if (! $this->consoleOutput->getFormatter()->hasStyle('warning')) {
            $style = new OutputFormatterStyle('yellow');

            $this->consoleOutput->getFormatter()->setStyle('warning', $style);
        }

        $this->line($message, 'warning');
    }

    /**
     * Trigger an Error message.
     *
     * @param  string  $message
     * @return void
     */
    protected function error(string $message)
    {
        $this->line($message, 'error');
    }

    protected function line(string $message, string $style): void
    {
        $this->consoleOutput->writeln(
            $style ? "<{$style}>{$message}</{$style}>" : $message
        );
    }
}
