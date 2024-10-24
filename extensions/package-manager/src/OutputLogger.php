<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager;

use Psr\Log\LoggerInterface;

class OutputLogger
{
    public function __construct(
        protected LoggerInterface $logger
    ) {
    }

    public function log(string $input, string $output, int $exitCode): void
    {
        $content = "$input\n$output";

        if ($exitCode === 0) {
            $this->logger->info($content);
        } else {
            $this->logger->error($content);
        }
    }
}
