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

    const EMPTY_REVISION = 'empty';

    protected VersionerInterface $versioner;

    public function __construct(
        protected Cloud $assetsDir,
        protected string $filename,
        protected SettingsRepositoryInterface $settings
    ) {
        $this->versioner = new FileVersioner($assetsDir);
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

        $oldRevision = $this->versioner->getRevision($this->filename);

        $newRevision = $this->calculateRevision($sources);

        // In case the previous and current revisions do not match
        // Or no file was written yet, let's save the file to disk.
        if ($force || $oldRevision !== $newRevision || ! $this->assetsDir->exists($this->filename)) {
            if (! $this->save($this->filename, $sources)) {
                // If no file was written (because the sources were empty), we
                // will set the revision to a special value so that we can tell
                // that this file does not have a URL.
                $newRevision = static::EMPTY_REVISION;
            }

            $this->versioner->putRevision($this->filename, $newRevision);
        }
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
     * @param string $file
     * @param SourceInterface[] $sources
     * @return bool true if the file was written, false if there was nothing to write
     */
    protected function save(string $file, array $sources): bool
    {
        if ($content = $this->compile($sources)) {
            $this->assetsDir->put($file, $content);

            return true;
        }

        return false;
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

    /**
     * @param SourceInterface[] $sources
     */
    protected function calculateRevision(array $sources): string
    {
        $cacheDifferentiator = [$this->getCacheDifferentiator()];

        foreach ($sources as $source) {
            $cacheDifferentiator[] = $source->getCacheDifferentiator();
        }

        return hash('crc32b', serialize($cacheDifferentiator));
    }

    protected function getCacheDifferentiator(): ?array
    {
        return null;
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
