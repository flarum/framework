<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Tests\Unit;

use Flarum\ExtensionManager\Apply\Journal;
use PHPUnit\Framework\TestCase;

/**
 * The journal's own guarantees, separately from the applier that uses it.
 *
 * These matter because the interesting cases are the ugly ones: a file whose
 * last line is half written, an entry that was begun and never finished. Both
 * are the NORMAL result of a killed process, and treating either as corruption
 * would turn a recoverable interruption into a manual one.
 */
class JournalTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        $this->path = sys_get_temp_dir().'/mw-journal-'.bin2hex(random_bytes(6)).'.jsonl';
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
    }

    public function test_an_entry_is_on_disk_before_begin_returns(): void
    {
        // The entire safety argument rests on this ordering: the record exists
        // before the action it describes is taken.
        $journal = new Journal($this->path);
        $seq = $journal->begin(['change' => ['op' => 'replace', 'package' => 'a/b']]);

        $this->assertSame(1, $seq);
        $this->assertStringContainsString('a/b', (string) file_get_contents($this->path));
    }

    public function test_sequence_numbers_increment_across_reopens(): void
    {
        // A resumed run constructs a new Journal over the same file; it must not
        // restart numbering and collide with what is already recorded.
        (new Journal($this->path))->begin(['change' => ['package' => 'a/one']]);
        $second = (new Journal($this->path))->begin(['change' => ['package' => 'a/two']]);

        $this->assertSame(2, $second);
    }

    public function test_a_begun_entry_is_reported_as_interrupted_until_completed(): void
    {
        $journal = new Journal($this->path);
        $seq = $journal->begin(['change' => ['package' => 'a/b']]);

        $this->assertCount(1, $journal->interrupted());
        $this->assertFalse($journal->isComplete());

        $journal->complete($seq);

        $this->assertSame([], $journal->interrupted());
        $this->assertTrue($journal->isComplete());
    }

    public function test_a_half_written_final_line_is_discarded_not_fatal(): void
    {
        // 🚨 Exactly what a SIGKILL mid-write leaves. The entries before it are
        // still authoritative and must survive; the fragment describes an action
        // that had not been recorded, so it had not started either.
        $journal = new Journal($this->path);
        $seq = $journal->begin(['change' => ['package' => 'a/first']]);
        $journal->complete($seq);

        file_put_contents($this->path, '{"seq":2,"state":"begun","change":{"pack', FILE_APPEND);

        $entries = $journal->entries();

        $this->assertCount(1, $entries);
        $this->assertSame('a/first', $entries[0]['change']['package']);
        $this->assertTrue($journal->isComplete());
    }

    public function test_a_blank_or_junk_line_does_not_derail_the_record(): void
    {
        $journal = new Journal($this->path);
        $journal->complete($journal->begin(['change' => ['package' => 'a/first']]));

        file_put_contents($this->path, "\n\nnot json at all\n", FILE_APPEND);

        $journal->complete($journal->begin(['change' => ['package' => 'a/second']]));

        $packages = array_column(array_column($journal->entries(), 'change'), 'package');

        $this->assertSame(['a/first', 'a/second'], $packages);
    }

    public function test_an_empty_or_absent_journal_is_not_complete(): void
    {
        // Guards a subtle wrong answer: "no interrupted entries" must not be
        // read as "a finished apply" when nothing ever ran.
        $journal = new Journal($this->path);

        $this->assertFalse($journal->exists());
        $this->assertSame([], $journal->entries());
        $this->assertFalse($journal->isComplete());
    }

    public function test_entries_keep_the_order_they_were_written_in(): void
    {
        // Rollback replays these in reverse, so order is not cosmetic.
        $journal = new Journal($this->path);

        foreach (['a/one', 'a/two', 'a/three'] as $pkg) {
            $journal->complete($journal->begin(['change' => ['package' => $pkg]]));
        }

        $packages = array_column(array_column($journal->entries(), 'change'), 'package');

        $this->assertSame(['a/one', 'a/two', 'a/three'], $packages);
    }
}
