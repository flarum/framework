<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler;

use Flarum\Frontend\Compiler\Source\SourceCollector;
use Flarum\Frontend\Compiler\Source\SourceInterface;
use Illuminate\Contracts\Filesystem\Cloud;

/**
 * @internal
 */
class RevisionCompiler implements CompilerInterface
{
    const EMPTY_REVISION = 'empty';

    /**
     * @var Cloud
     */
    protected $assetsDir;

    /**
     * @var VersionerInterface
     */
    protected $versioner;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var callable[]
     */
    protected $sourcesCallbacks = [];

    /**
     * @param Cloud $assetsDir
     * @param string $filename
     * @param VersionerInterface|null $versioner @deprecated nullable will be removed at v2.0
     */
    public function __construct(Cloud $assetsDir, string $filename, VersionerInterface $versioner = null)
    {
        $this->assetsDir = $assetsDir;
        $this->filename = $filename;
        $this->versioner = $versioner ?: new FileVersioner($assetsDir);
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename)
    {
        $this->filename = $filename;
    }

    public function commit(bool $force = false)
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

    public function addSources(callable $callback)
    {
        $this->sourcesCallbacks[] = $callback;
    }

    /**
     * @return SourceInterface[]
     */
    protected function getSources(): array
    {
        $sources = new SourceCollector;

        foreach ($this->sourcesCallbacks as $callback) {
            $callback($sources);
        }

        return $sources->getSources();
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

    public function flush()
    {
        if ($this->versioner->getRevision($this->filename) !== null) {
            $this->delete($this->filename);

            $this->versioner->putRevision($this->filename, null);
        }
    }

    protected function delete(string $file)
    {
        if ($this->assetsDir->exists($file)) {
            $this->assetsDir->delete($file);
        }
    }
}
