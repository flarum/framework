<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Frontend\Compiler;

use Flarum\Frontend\Compiler\DatabaseVersioner;
use Flarum\Testing\unit\TestCase;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;

/**
 * Runs against a real SQLite database rather than a mocked connection: the
 * whole point of this class is what the storage does under concurrent writes,
 * and a mock would only replay whatever assumption was written into it.
 */
class DatabaseVersionerTest extends TestCase
{
    private Capsule $capsule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->capsule = new Capsule();
        $this->capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        // Without a dispatcher, Connection::listen() is a silent no-op and the
        // query-count assertions below would pass on any implementation.
        $this->capsule->setEventDispatcher(new Dispatcher());

        $schema = $this->connection()->getSchemaBuilder();
        $schema->create('asset_revisions', function ($table) {
            $table->string('file', 255)->primary();
            $table->string('revision', 32);
        });
    }

    private function connection(): ConnectionInterface
    {
        return $this->capsule->getConnection();
    }

    private function versioner(): DatabaseVersioner
    {
        return new DatabaseVersioner($this->connection());
    }

    private function rowCount(): int
    {
        return $this->connection()->table('asset_revisions')->count();
    }

    #[Test]
    public function it_round_trips_a_revision()
    {
        $versioner = $this->versioner();

        $versioner->putRevision('forum.js', 'abc12345');

        $this->assertSame('abc12345', $versioner->getRevision('forum.js'));
        $this->assertSame(['forum.js' => 'abc12345'], $versioner->allRevisions());
        $this->assertSame(1, $this->rowCount());
    }

    #[Test]
    public function a_missing_revision_reads_as_null()
    {
        $this->assertNull($this->versioner()->getRevision('nope.js'));
    }

    #[Test]
    public function overwriting_a_revision_keeps_one_row()
    {
        $versioner = $this->versioner();

        $versioner->putRevision('forum.js', 'aaaaaaaa');
        $versioner->putRevision('forum.js', 'bbbbbbbb');

        $this->assertSame('bbbbbbbb', $versioner->getRevision('forum.js'));
        $this->assertSame(1, $this->rowCount(), 'an update must not insert a second row');
    }

    #[Test]
    public function a_null_revision_removes_the_row()
    {
        $versioner = $this->versioner();
        $versioner->putRevision('forum.js', 'abc12345');

        $versioner->putRevision('forum.js', null);

        $this->assertNull($versioner->getRevision('forum.js'));
        $this->assertSame(0, $this->rowCount());
    }

    /**
     * The defect this class removes. Holding the whole map in one value means
     * each writer reads it, edits its own key and writes the lot back — so a
     * writer carrying a snapshot from before someone else's write silently
     * erases it. Measured at 50% loss across two real ECS tasks writing 20
     * keys each, and 83% across six local processes.
     *
     * Two instances stand in for two pods. Both read the map first, so each
     * holds a pre-write snapshot; both writes must still survive.
     */
    #[Test]
    public function concurrent_writers_do_not_erase_each_other()
    {
        $podA = $this->versioner();
        $podB = $this->versioner();

        $podA->allRevisions();
        $podB->allRevisions();

        $podA->putRevision('forum.js', 'aaaaaaaa');
        $podB->putRevision('admin.js', 'bbbbbbbb');

        $fresh = $this->versioner();

        $this->assertSame('aaaaaaaa', $fresh->getRevision('forum.js'), "pod B's write erased pod A's");
        $this->assertSame('bbbbbbbb', $fresh->getRevision('admin.js'), "pod A's write erased pod B's");
        $this->assertSame(2, $this->rowCount());
    }

    /**
     * Interleaved the other way round: A reads, B writes and A then writes.
     * A's stale snapshot must not resurrect what B removed.
     */
    #[Test]
    public function a_stale_snapshot_does_not_resurrect_a_removed_revision()
    {
        $seed = $this->versioner();
        $seed->putRevision('chunk.js', 'aaaaaaaa');

        $podA = $this->versioner();
        $podA->allRevisions();          // snapshot includes chunk.js

        $podB = $this->versioner();
        $podB->putRevision('chunk.js', null);   // B prunes it

        $podA->putRevision('forum.js', 'bbbbbbbb');   // A writes something else

        $fresh = $this->versioner();

        $this->assertNull($fresh->getRevision('chunk.js'), "A's snapshot resurrected a pruned chunk");
        $this->assertSame('bbbbbbbb', $fresh->getRevision('forum.js'));
    }

    /**
     * JsDirectoryCompiler::pruneStaleRevisions() calls putRevision() once per
     * stale chunk, and it runs from getUrl() — on ordinary page renders, not
     * only on rebuilds. A render that prunes nothing must write nothing.
     */
    #[Test]
    public function writing_an_unchanged_revision_touches_no_storage()
    {
        $versioner = $this->versioner();
        $versioner->putRevision('forum.js', 'abc12345');

        $writes = 0;
        $this->connection()->listen(function () use (&$writes) {
            $writes++;
        });

        $versioner->putRevision('forum.js', 'abc12345');

        $this->assertSame(0, $writes, 'an unchanged revision must not be rewritten');
    }

    #[Test]
    public function removing_an_already_absent_revision_touches_no_storage()
    {
        $versioner = $this->versioner();
        $versioner->allRevisions();

        $queries = 0;
        $this->connection()->listen(function () use (&$queries) {
            $queries++;
        });

        $versioner->putRevision('forum.js', null);

        $this->assertSame(0, $queries, 'nothing to delete means no query');
    }

    /**
     * Frontend rendering reads the map ~31 times and individual revisions ~65
     * times per page, all through one shared instance. Reading storage each
     * time would put a query on every one of those.
     */
    #[Test]
    public function the_map_is_read_from_storage_once_per_instance()
    {
        $seed = $this->versioner();
        $seed->putRevision('forum.js', 'abc12345');

        $versioner = $this->versioner();

        $queries = 0;
        $this->connection()->listen(function () use (&$queries) {
            $queries++;
        });

        $versioner->allRevisions();
        $versioner->allRevisions();
        $versioner->getRevision('forum.js');
        $versioner->getRevision('admin.js');

        $this->assertSame(1, $queries, 'the map must be fetched once and memoised');
    }

    #[Test]
    public function a_write_keeps_the_memo_in_step()
    {
        $versioner = $this->versioner();
        $versioner->allRevisions();

        $versioner->putRevision('forum.js', 'abc12345');

        $queries = 0;
        $this->connection()->listen(function () use (&$queries) {
            $queries++;
        });

        $this->assertSame('abc12345', $versioner->getRevision('forum.js'));
        $this->assertSame(0, $queries, 'a write must update the memo, not invalidate it');
    }

    /**
     * Code-split chunks carry slashes and dots and make up the bulk of a real
     * manifest — 87 of 109 keys on a working forum.
     */
    #[Test]
    public function it_handles_code_split_chunk_names()
    {
        $versioner = $this->versioner();

        $chunk = 'js/fof-profile-image-crop/forum/components/ProfileImageCropModal.js';
        $versioner->putRevision($chunk, '0cb639f4');

        $this->assertSame('0cb639f4', $versioner->getRevision($chunk));
        $this->assertSame([$chunk => '0cb639f4'], $versioner->allRevisions());
    }

    /**
     * A bundle with no sources legitimately has no file, and records
     * RevisionCompiler::EMPTY_REVISION rather than being absent — 13 of the
     * 109 rows on a working forum.
     */
    #[Test]
    public function it_stores_the_empty_revision_marker()
    {
        $versioner = $this->versioner();

        $versioner->putRevision('admin-en.css', 'empty');

        $this->assertSame('empty', $versioner->getRevision('admin-en.css'));
    }

    /**
     * A rebuild records every asset in turn — around eighteen on a plain forum,
     * and pruning stale chunks can remove dozens more. One row per statement
     * turns that into a round trip per asset, which is what the file-backed
     * versioner avoided by writing the map once.
     */
    #[Test]
    public function deferred_writes_are_stored_in_a_single_statement()
    {
        $versioner = $this->versioner();

        $queries = [];
        $this->connection()->listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $versioner->deferWrites();

        for ($i = 0; $i < 18; $i++) {
            $versioner->putRevision("chunk$i.js", sprintf('%08x', $i));
        }

        $versioner->flushWrites();

        $writes = array_values(array_filter($queries, fn (string $sql) => ! str_starts_with($sql, 'select')));

        $this->assertCount(1, $writes, 'eighteen revisions must be stored together');
        $this->assertSame(18, $this->rowCount());
    }

    #[Test]
    public function deferred_removals_are_stored_in_a_single_statement()
    {
        $seed = $this->versioner();
        $seed->deferWrites();
        for ($i = 0; $i < 10; $i++) {
            $seed->putRevision("chunk$i.js", sprintf('%08x', $i));
        }
        $seed->flushWrites();

        $versioner = $this->versioner();

        $queries = [];
        $this->connection()->listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $versioner->deferWrites();
        for ($i = 0; $i < 10; $i++) {
            $versioner->putRevision("chunk$i.js", null);
        }
        $versioner->flushWrites();

        $deletes = array_values(array_filter($queries, fn (string $sql) => str_starts_with($sql, 'delete')));

        $this->assertCount(1, $deletes, 'stale chunks must be pruned together');
        $this->assertSame(0, $this->rowCount());
    }

    #[Test]
    public function a_deferred_write_is_visible_to_this_instance_before_it_is_stored()
    {
        $versioner = $this->versioner();

        $versioner->deferWrites();
        $versioner->putRevision('forum.js', 'abc12345');

        $this->assertSame('abc12345', $versioner->getRevision('forum.js'));
        $this->assertSame(['forum.js' => 'abc12345'], $versioner->allRevisions());
    }

    /**
     * Reading during a batch must NOT store it: getUrl() records a revision and
     * then reads it straight back, so a read that settled the batch would drain
     * it once per asset and defeat the batching entirely. The memo already
     * carries deferred writes, so the read is answered without them.
     */
    #[Test]
    public function reading_during_a_batch_does_not_store_it()
    {
        $versioner = $this->versioner();

        $versioner->deferWrites();
        $versioner->putRevision('forum.js', 'abc12345');

        $queries = [];
        $this->connection()->listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->assertSame('abc12345', $versioner->getRevision('forum.js'), 'a read must see the pending write');
        $versioner->allRevisions();

        $writes = array_filter($queries, fn (string $sql) => ! str_starts_with($sql, 'select'));

        $this->assertSame([], array_values($writes), 'a read must not drain the batch');
    }

    /**
     * The one way buffering could lose data is a batch that is never flushed —
     * a caller that forgets, or a rebuild that throws past its own flush.
     * Discarding the instance stores whatever is outstanding.
     */
    #[Test]
    public function an_abandoned_batch_is_stored_when_the_instance_goes_away()
    {
        $versioner = $this->versioner();

        $versioner->deferWrites();
        $versioner->putRevision('forum.js', 'abc12345');

        unset($versioner);

        $this->assertSame('abc12345', $this->versioner()->getRevision('forum.js'), 'an abandoned batch was lost');
    }

    #[Test]
    public function writes_are_stored_immediately_when_nothing_is_deferred()
    {
        $versioner = $this->versioner();

        $versioner->putRevision('forum.js', 'abc12345');

        $this->assertSame('abc12345', $this->versioner()->getRevision('forum.js'));
    }

    #[Test]
    public function flushing_without_deferring_anything_is_harmless()
    {
        $versioner = $this->versioner();

        $queries = 0;
        $this->connection()->listen(function () use (&$queries) {
            $queries++;
        });

        $versioner->flushWrites();
        $versioner->flushWrites();

        $this->assertSame(0, $queries);
    }

    #[Test]
    public function deferring_still_skips_writes_that_change_nothing()
    {
        $seed = $this->versioner();
        $seed->putRevision('forum.js', 'abc12345');

        $versioner = $this->versioner();

        $queries = [];
        $this->connection()->listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $versioner->deferWrites();
        $versioner->putRevision('forum.js', 'abc12345');
        $versioner->flushWrites();

        $writes = array_filter($queries, fn (string $sql) => ! str_starts_with($sql, 'select'));

        $this->assertSame([], array_values($writes), 'an unchanged revision must not be stored');
    }

    #[Test]
    public function a_mixed_batch_stores_and_removes_correctly()
    {
        $seed = $this->versioner();
        $seed->putRevision('stale.js', 'aaaaaaaa');
        $seed->putRevision('kept.js', 'bbbbbbbb');

        $versioner = $this->versioner();
        $versioner->deferWrites();
        $versioner->putRevision('stale.js', null);
        $versioner->putRevision('fresh.js', 'cccccccc');
        $versioner->putRevision('kept.js', 'dddddddd');
        $versioner->flushWrites();

        $fresh = $this->versioner();

        $this->assertNull($fresh->getRevision('stale.js'));
        $this->assertSame('cccccccc', $fresh->getRevision('fresh.js'));
        $this->assertSame('dddddddd', $fresh->getRevision('kept.js'));
    }

    #[Test]
    public function it_reads_a_full_manifest_back_in_one_query()
    {
        $seed = $this->versioner();

        foreach (['forum.js' => 'aaaaaaaa', 'forum.css' => 'bbbbbbbb', 'admin.js' => 'cccccccc'] as $file => $rev) {
            $seed->putRevision($file, $rev);
        }

        $this->assertSame([
            'forum.js' => 'aaaaaaaa',
            'forum.css' => 'bbbbbbbb',
            'admin.js' => 'cccccccc',
        ], $this->versioner()->allRevisions());
    }
}
