<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mcp\Tests\unit;

use Flarum\Mcp\Server\SdkLogger;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Stringable;

class SdkLoggerTest extends TestCase
{
    #[Test]
    public function only_warnings_and_worse_get_through_outside_debug_mode(): void
    {
        $this->assertSame(['warning: [mcp] w', 'error: [mcp] e'], $this->logged(debug: false));
    }

    #[Test]
    public function everything_gets_through_in_debug_mode(): void
    {
        $this->assertSame(['debug: [mcp] d', 'info: [mcp] i', 'warning: [mcp] w', 'error: [mcp] e'], $this->logged(debug: true));
    }

    /**
     * @return list<string>
     */
    protected function logged(bool $debug): array
    {
        $inner = new class extends AbstractLogger {
            /** @var list<string> */
            public array $lines = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->lines[] = "$level: $message";
            }
        };

        $logger = new SdkLogger($inner, $debug);
        $logger->debug('d');
        $logger->info('i');
        $logger->warning('w');
        $logger->error('e');

        return $inner->lines;
    }
}
