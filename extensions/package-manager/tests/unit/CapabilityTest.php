<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Host\Capability;
use Flarum\ExtensionManager\Host\PhpBinary;
use PHPUnit\Framework\TestCase;

/**
 * The thresholds here are the measured ones, and getting them wrong would be
 * worse than having no panel at all: a host told it can update Flarum, that
 * cannot, fails halfway through instead of before starting.
 */
class CapabilityTest extends TestCase
{
    private function withMemory(string $limit, callable $fn): mixed
    {
        $was = ini_get('memory_limit');
        ini_set('memory_limit', $limit);

        try {
            return $fn(new Capability(sys_get_temp_dir(), new PhpBinary()));
        } finally {
            ini_set('memory_limit', $was);
        }
    }

    public function test_128mb_cannot_resolve_at_all(): void
    {
        // Measured: a targeted resolve peaks at 123 MB, which does not fit here.
        $this->assertSame(Capability::NONE, $this->withMemory('128M', fn ($c) => $c->resolveTier()));
    }

    public function test_160mb_can_do_targeted_updates_only(): void
    {
        $this->assertSame(Capability::TARGETED, $this->withMemory('160M', fn ($c) => $c->resolveTier()));
    }

    public function test_192mb_can_do_everything(): void
    {
        // Measured: a full resolve of 253 packages peaks at 162 MB.
        $this->assertSame(Capability::FULL, $this->withMemory('192M', fn ($c) => $c->resolveTier()));
    }

    public function test_an_unlimited_host_can_do_everything(): void
    {
        $this->assertSame(Capability::FULL, $this->withMemory('-1', fn ($c) => $c->resolveTier()));
    }

    public function test_every_check_explains_itself(): void
    {
        // 🚨 A capability panel that lists facts without consequences is the same
        // failure as a spinner: technically informative, practically useless.
        $report = (new Capability(sys_get_temp_dir(), new PhpBinary()))->report();

        $this->assertNotEmpty($report['checks']);

        foreach ($report['checks'] as $check) {
            // Translated rows carry keys; the page turns them into words.
            if (isset($check['whatKey'])) {
                $this->assertNotEmpty($check['whyKeys'], "'{$check['whatKey']}' does not say what it means for the user");
                continue;
            }

            $this->assertNotEmpty($check['what'], 'a check with no label');
            $this->assertGreaterThan(40, strlen($check['why']), "'{$check['what']}' does not say what it means for the user");
        }

        $this->assertGreaterThan(40, strlen($report['summary']));
    }

    public function test_a_constrained_host_is_told_what_to_ask_for(): void
    {
        $summary = $this->withMemory('128M', fn ($c) => $c->report()['summary']);

        $this->assertStringContainsString('memory_limit', $summary);
        $this->assertStringContainsString('256', $summary, 'it should name the number to ask the host for');
    }
}
