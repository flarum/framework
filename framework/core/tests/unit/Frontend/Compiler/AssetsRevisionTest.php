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

    #[Test]
    public function token_ignores_admin_only_assets()
    {
        // The forum client never loads admin assets, so admin-only churn (e.g.
        // toggling an admin-only extension, which rebuilds the admin bundle and
        // its admin chunks) must not move the forum token — otherwise every
        // visitor is prompted to reload for a change that can't affect them.
        $base = AssetsRevision::tokenFor([
            'forum.js' => '1',
            'admin.js' => 'a',
            'admin.css' => 'a',
            'admin-en.js' => 'a',
            'js/fof-example/admin/Page.js' => 'a',
        ]);
        $adminChanged = AssetsRevision::tokenFor([
            'forum.js' => '1',
            'admin.js' => 'b',
            'admin.css' => 'b',
            'admin-en.js' => 'b',
            'js/fof-example/admin/Page.js' => 'b',
        ]);

        $this->assertEquals($base, $adminChanged);
    }

    #[Test]
    public function token_changes_when_a_forum_or_shared_chunk_changes()
    {
        // Split chunks the forum can load ARE part of what the client needs to
        // know about: a change to a forum or shared (common) chunk must move
        // the token.
        $base = AssetsRevision::tokenFor([
            'forum.js' => '1',
            'js/fof-blog/forum/pages/BlogComposer.js' => '1',
            'js/flarum-tags/common/components/TagSelectionModal.js' => '1',
        ]);

        $forumChunkChanged = AssetsRevision::tokenFor([
            'forum.js' => '1',
            'js/fof-blog/forum/pages/BlogComposer.js' => '2',
            'js/flarum-tags/common/components/TagSelectionModal.js' => '1',
        ]);

        $commonChunkChanged = AssetsRevision::tokenFor([
            'forum.js' => '1',
            'js/fof-blog/forum/pages/BlogComposer.js' => '1',
            'js/flarum-tags/common/components/TagSelectionModal.js' => '2',
        ]);

        $this->assertNotEquals($base, $forumChunkChanged, 'a forum chunk change must move the token');
        $this->assertNotEquals($base, $commonChunkChanged, 'a shared (forum-loadable) chunk change must move the token');
    }
}
