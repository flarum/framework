<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Install\Prerequisite;

use Flarum\Install\Prerequisite\AnyPhpExtension;
use Flarum\Testing\unit\TestCase;

class AnyPhpExtensionTest extends TestCase
{
    public function test_no_problems_when_one_of_the_extensions_is_loaded()
    {
        $prerequisite = new AnyPhpExtension(['flarum_test_missing', 'json']);

        $this->assertCount(0, $prerequisite->problems());
    }

    public function test_one_problem_naming_every_extension_when_none_is_loaded()
    {
        $prerequisite = new AnyPhpExtension(['flarum_test_a', 'flarum_test_b', 'flarum_test_c']);

        $problems = $prerequisite->problems();

        $this->assertCount(1, $problems);
        $this->assertEquals(
            "One of the PHP extensions 'flarum_test_a', 'flarum_test_b' or 'flarum_test_c' is required.",
            $problems[0]['message']
        );
    }
}
