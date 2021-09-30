<?php

namespace SychO\PackageManager;

use Psr\Log\LoggerInterface;

class OutputLogger
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
