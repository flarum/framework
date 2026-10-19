<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Work\FlarumCommand;
use PHPUnit\Framework\TestCase;

/**
 * What a failed `php flarum …` shows the person reading it.
 */
class FailureExcerptTest extends TestCase
{
    /**
     * The customer case: an Xdebug trace prints the error first and then a
     * frame per line. The old tail-only cut kept frames 8–19 and lost the error.
     */
    public function test_a_long_xdebug_trace_keeps_the_error_line_at_the_top(): void
    {
        $output = "PHP Fatal error:  Allowed memory size of 134217728 bytes exhausted in LessCompiler.php on line 268\n"
            ."PHP Stack trace:\n";
        for ($i = 1; $i <= 19; $i++) {
            $output .= "PHP  {$i}. Frame{$i}->call(".str_repeat('x', 5000).") file.php:{$i}\n";
        }

        $excerpt = FlarumCommand::excerpt($output);
        $lines = explode("\n", $excerpt);

        $this->assertStringStartsWith('PHP Fatal error:  Allowed memory size', $lines[0]);
        $this->assertContains('…', $lines);
        $this->assertStringStartsWith('PHP  19. Frame19', end($lines));
        $this->assertCount(4 + 1 + 8, $lines);
        // One frame's dumped arguments cannot bury the rest.
        foreach ($lines as $line) {
            $this->assertLessThanOrEqual(300, mb_strwidth($line));
        }
    }

    public function test_short_output_is_shown_whole_without_blank_lines(): void
    {
        $this->assertSame("In Foo.php line 3:\nBoom", FlarumCommand::excerpt("\n  \nIn Foo.php line 3:\n\n  \nBoom\n"));
    }
}
