<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler;

use Flarum\Frontend\Compiler\Concerns\HasSources;
use Flarum\Frontend\Compiler\Source\FileSource;
use Flarum\Frontend\Compiler\Source\SourceInterface;
use Flarum\Frontend\Compiler\Source\StringSource;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Cloud;

/**
 * @internal
 */
class RevisionCompiler implements CompilerInterface
{
    use HasSources;

    public const EMPTY_REVISION = 'empty';

    public function __construct(
        protected Cloud $assetsDir,
        protected string $filename,
        protected SettingsRepositoryInterface $settings,
        protected VersionerInterface $versioner,
    ) {
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): void
    {
        $this->filename = $filename;
    }

    public function commit(bool $force = false): void
    {
        $sources = $this->getSources();

        // Render the output once and derive the revision from it. The revision
        // must change if and only if the bytes a client would download change —
        // so it is a hash of the compiled OUTPUT, not of source paths/mtimes
        // (which move on every redeploy or extension toggle even when the result
        // is byte-identical, and can miss a real change made within the same
        // second) and not of the raw source content (which for LESS misses
        // @import contents and variable/import overrides, and for JS misses the
        // sourcemap and format rewrite the written file actually carries).
        $output = $this->renderOutput($sources);

        $newRevision = $output === null ? static::EMPTY_REVISION : $this->hashOutput($output);

        $oldRevision = $this->versioner->getRevision($this->filename);

        if ($force || $oldRevision !== $newRevision || ! $this->assetsDir->exists($this->filename)) {
            if ($output !== null) {
                $this->assetsDir->put($this->filename, $output);
            }

            $this->versioner->putRevision($this->filename, $newRevision);
        }
    }

    protected function hashOutput(string $content): string
    {
        return hash('crc32b', $content);
    }

    /**
     * Produce the exact bytes to write to the primary asset file, or null when
     * there is nothing to write (empty sources). This is what the revision is
     * hashed from, so it must be identical to what gets committed — subclasses
     * that transform or add to the output (e.g. JsCompiler's sourcemap handling)
     * override this and may write sidecar files (like the `.map`) as a side
     * effect, as long as the returned string is the primary file's content.
     *
     * @param SourceInterface[] $sources
     */
    protected function renderOutput(array $sources): ?string
    {
        $content = $this->compile($sources);

        return $content === '' ? null : $content;
    }

    public function getUrl(): ?string
    {
        $revision = $this->versioner->getRevision($this->filename);

        if (! $revision) {
            $this->commit();

            $revision = $this->versioner->getRevision($this->filename);

            if (! $revision) {
                return null;
            }
        }

        if ($revision === static::EMPTY_REVISION) {
            return null;
        }

        $url = $this->assetsDir->url($this->filename);

        // Append revision as GET param to signify that there's been
        // a change to the file and it should be refreshed.
        return "$url?v=$revision";
    }

    /**
     * @param SourceInterface[] $sources
     */
    protected function compile(array $sources): string
    {
        $output = '';

        foreach ($sources as $source) {
            $output .= $this->format($source->getContent());
        }

        return $output;
    }

    protected function format(string $string): string
    {
        return $string;
    }

    public function flush(): void
    {
        if ($this->versioner->getRevision($this->filename) !== null) {
            $this->delete($this->filename);

            $this->versioner->putRevision($this->filename, null);
        }
    }

    protected function delete(string $file): void
    {
        if ($this->assetsDir->exists($file)) {
            $this->assetsDir->delete($file);
        }
    }

    protected function allowedSourceTypes(): array
    {
        return [FileSource::class, StringSource::class];
    }
}
