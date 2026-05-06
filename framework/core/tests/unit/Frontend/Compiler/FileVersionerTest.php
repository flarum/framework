<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Frontend\Compiler;

use Flarum\Frontend\Compiler\FileVersioner;
use Flarum\Testing\unit\TestCase;
use Illuminate\Contracts\Filesystem\Filesystem;
use Mockery as m;
use PHPUnit\Framework\Attributes\Test;

class FileVersionerTest extends TestCase
{
    #[Test]
    public function it_reads_the_manifest_from_disk_only_once_per_instance()
    {
        $filesystem = m::mock(Filesystem::class);
        $filesystem->shouldReceive('exists')->with(FileVersioner::REV_MANIFEST)->once()->andReturn(true);
        $filesystem->shouldReceive('get')->with(FileVersioner::REV_MANIFEST)->once()
            ->andReturn(json_encode(['foo.js' => 'abc123', 'bar.css' => 'def456']));

        $versioner = new FileVersioner($filesystem);

        // Three calls — should only hit the filesystem once.
        $this->assertSame('abc123', $versioner->getRevision('foo.js'));
        $this->assertSame('def456', $versioner->getRevision('bar.css'));
        $this->assertSame(['foo.js' => 'abc123', 'bar.css' => 'def456'], $versioner->allRevisions());
    }

    #[Test]
    public function it_returns_null_for_missing_keys()
    {
        $filesystem = m::mock(Filesystem::class);
        $filesystem->shouldReceive('exists')->with(FileVersioner::REV_MANIFEST)->andReturn(true);
        $filesystem->shouldReceive('get')->with(FileVersioner::REV_MANIFEST)
            ->andReturn(json_encode(['foo.js' => 'abc123']));

        $versioner = new FileVersioner($filesystem);

        $this->assertNull($versioner->getRevision('missing.js'));
    }

    #[Test]
    public function it_returns_empty_array_when_manifest_does_not_exist()
    {
        $filesystem = m::mock(Filesystem::class);
        $filesystem->shouldReceive('exists')->with(FileVersioner::REV_MANIFEST)->once()->andReturn(false);
        $filesystem->shouldNotReceive('get');

        $versioner = new FileVersioner($filesystem);

        $this->assertNull($versioner->getRevision('foo.js'));
        $this->assertSame([], $versioner->allRevisions());
    }

    #[Test]
    public function put_revision_writes_through_and_updates_in_memory_cache()
    {
        $filesystem = m::mock(Filesystem::class);
        $filesystem->shouldReceive('exists')->with(FileVersioner::REV_MANIFEST)->once()->andReturn(true);
        $filesystem->shouldReceive('get')->with(FileVersioner::REV_MANIFEST)->once()
            ->andReturn(json_encode(['foo.js' => 'old']));
        $filesystem->shouldReceive('put')->with(FileVersioner::REV_MANIFEST, json_encode(['foo.js' => 'new']))->once();

        $versioner = new FileVersioner($filesystem);
        $versioner->putRevision('foo.js', 'new');

        // Subsequent reads should NOT hit the filesystem again — the in-memory
        // cache must reflect the write-through.
        $this->assertSame('new', $versioner->getRevision('foo.js'));
    }

    #[Test]
    public function put_revision_with_null_removes_the_entry()
    {
        $filesystem = m::mock(Filesystem::class);
        $filesystem->shouldReceive('exists')->with(FileVersioner::REV_MANIFEST)->andReturn(true);
        $filesystem->shouldReceive('get')->with(FileVersioner::REV_MANIFEST)
            ->andReturn(json_encode(['foo.js' => 'abc', 'bar.css' => 'def']));
        $filesystem->shouldReceive('put')->with(FileVersioner::REV_MANIFEST, json_encode(['bar.css' => 'def']))->once();

        $versioner = new FileVersioner($filesystem);
        $versioner->putRevision('foo.js', null);

        $this->assertNull($versioner->getRevision('foo.js'));
        $this->assertSame('def', $versioner->getRevision('bar.css'));
    }
}
