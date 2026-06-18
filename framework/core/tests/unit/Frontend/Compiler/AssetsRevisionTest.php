<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Frontend\Compiler;

use Flarum\Frontend\Compiler\AssetsRevision;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AssetsRevisionTest extends TestCase
{
    #[Test]
    public function token_is_independent_of_key_order()
    {
        // The client reconstructs the token from a JS object whose key order is not
        // guaranteed to match PHP's, so the same entries must always hash the same.
        $a = AssetsRevision::tokenFor(['forum.js' => '1', 'forum.css' => '2', 'admin.js' => '3']);
        $b = AssetsRevision::tokenFor(['admin.js' => '3', 'forum.css' => '2', 'forum.js' => '1']);

        $this->assertEquals($a, $b);
    }

    #[Test]
    public function token_changes_when_any_revision_changes()
    {
        $base = AssetsRevision::tokenFor(['forum.js' => '1', 'forum.css' => '2']);
        $changed = AssetsRevision::tokenFor(['forum.js' => '1', 'forum.css' => '2-updated']);

        $this->assertNotEquals($base, $changed);
    }

    #[Test]
    public function token_changes_when_a_file_is_added_or_removed()
    {
        $base = AssetsRevision::tokenFor(['forum.js' => '1']);
        $added = AssetsRevision::tokenFor(['forum.js' => '1', 'chunk.js' => '9']);

        $this->assertNotEquals($base, $added);
    }
}
