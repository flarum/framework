<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Queue;

use Flarum\Queue\ExceptionHandler;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

class ExceptionHandlerTest extends TestCase
{
    private ExceptionHandler $handler;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = new ExceptionHandler($this->logger);
    }

    #[Test]
    public function report_logs_exception_as_error(): void
    {
        $e = new RuntimeException('Something went wrong');

        $this->logger->expects($this->once())
            ->method('error')
            ->with((string) $e);

        $this->handler->report($e);
    }

    #[Test]
    public function render_rethrows_the_exception(): void
    {
        $e = new RuntimeException('Queue job failed');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Queue job failed');

        $this->handler->render(null, $e);
    }

    #[Test]
    public function render_for_console_writes_exception_to_output(): void
    {
        $e = new RuntimeException('Something went wrong');
        $output = $this->createMock(OutputInterface::class);

        $output->expects($this->once())
            ->method('writeln')
            ->with((string) $e);

        $this->handler->renderForConsole($output, $e);
    }

    #[Test]
    public function should_report_always_returns_true(): void
    {
        $this->assertTrue($this->handler->shouldReport(new RuntimeException()));
    }
}
