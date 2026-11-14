<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Job\ComposerCommandJob;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;

class ComposerCommandJobTest extends TestCase
{
    /**
     * As an earlier version serialized it: the command is an instance
     * of a class that no longer exists, carrying a task model that no longer
     * exists either.
     */
    #[Test]
    public function a_job_queued_by_an_earlier_version_unserializes_and_completes(): void
    {
        $task = 'Flarum\ExtensionManager\Task\Task';
        $class = 'Flarum\ExtensionManager\Command\RequireExtension';
        $command = sprintf('O:%d:"%s":1:{s:4:"task";O:%d:"%s":0:{}}', strlen($class), $class, strlen($task), $task);
        $payload = sprintf(
            'O:%d:"%s":2:{s:10:"'."\0*\0".'command";%ss:13:"'."\0*\0".'phpVersion";s:5:"8.3.0";}',
            strlen(ComposerCommandJob::class),
            ComposerCommandJob::class,
            $command
        );

        $job = unserialize($payload);
        $this->assertInstanceOf(ComposerCommandJob::class, $job);

        $logger = new class extends AbstractLogger {
            /** @var list<string> */
            public array $lines = [];

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->lines[] = (string) $message;
            }
        };

        $job->handle($logger);

        $this->assertCount(1, $logger->lines);
    }
}
