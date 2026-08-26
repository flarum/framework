<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\console;

use Flarum\Formatter\Formatter;
use Flarum\Testing\integration\ConsoleTestCase;
use Illuminate\Contracts\Cache\Repository;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class CacheClearCommandTest extends ConsoleTestCase
{
    /**
     * @return array{output: string, status: int}
     */
    private function runCacheClear(): array
    {
        $output = new BufferedOutput();
        $status = $this->console()->run(new ArrayInput(['command' => 'cache:clear']), $output);

        return ['output' => trim($output->fetch()), 'status' => $status];
    }

    #[Test]
    public function it_succeeds()
    {
        $result = $this->runCacheClear();

        $this->assertEquals(Command::SUCCESS, $result['status'], $result['output']);
    }

    #[Test]
    public function it_leaves_the_formatter_warm()
    {
        /** @var Repository $cache */
        $cache = $this->app()->getContainer()->make(Repository::class);

        // Force the formatter cache cold, the state a clear would otherwise
        // leave the render path in.
        $this->app()->getContainer()->make(Formatter::class)->flush();
        $this->assertFalse($cache->has('flarum.formatter'), 'Precondition: the formatter cache should be cold.');

        $this->runCacheClear();

        // The command rebuilds the formatter itself, so the next render doesn't
        // have to — keeping the compile off the (memory-tight) web request that
        // would otherwise trigger it.
        $this->assertTrue($cache->has('flarum.formatter'), 'cache:clear should leave the formatter cache warm.');
    }
}
