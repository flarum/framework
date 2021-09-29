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

    public function log(string $output, int $exitCode): void
    {
        if ($exitCode === 0) {
            $this->logger->info($output);
        } else {
            $this->logger->error($output);
        }
    }
}
