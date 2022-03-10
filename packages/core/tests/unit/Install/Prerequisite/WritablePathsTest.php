<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Install\Prerequisite;

use Flarum\Install\Prerequisite\WritablePaths;
use Flarum\Testing\unit\TestCase;

class WritablePathsTest extends TestCase
{
    public function test_no_problems_when_all_directories_are_writable()
    {
        $writable = new WritablePaths([
            __DIR__.'/../../../fixtures/writable_paths/writable',
        ]);

        $this->assertCount(0, $writable->problems());
    }

    public function test_paths_can_be_given_with_wildcard()
    {
        $writable = new WritablePaths([
            __DIR__.'/../../../fixtures/writable_paths/writable/*',
        ]);

        $this->assertCount(0, $writable->problems());
    }

    public function test_problems_when_one_path_is_missing()
    {
        $writable = new WritablePaths([
            __DIR__.'/../../../fixtures/writable_paths/missing',
            __DIR__.'/../../../fixtures/writable_paths/writable',
        ]);

        $problems = $writable->problems();
        $this->assertCount(1, $problems);
        $this->assertMatchesRegularExpression(
            "%^The .+/tests/fixtures/writable_paths/missing directory doesn't exist$%",
            $problems[0]['message']
        );
        $this->assertEquals(
            'This directory is necessary for the installation. Please create the folder.',
            $problems[0]['detail']
        );
    }

    public function test_problem_details_filter_out_wildcard()
    {
        $writable = new WritablePaths([
            __DIR__.'/../../../fixtures/writable_paths/missing/*',
        ]);

        $problems = $writable->problems();
        $this->assertCount(1, $problems);
        $this->assertMatchesRegularExpression(
            "%^The .+/tests/fixtures/writable_paths/missing directory doesn't exist$%",
            $problems[0]['message']
        );
        $this->assertEquals(
            'This directory is necessary for the installation. Please create the folder.',
            $problems[0]['detail']
        );
    }
}
