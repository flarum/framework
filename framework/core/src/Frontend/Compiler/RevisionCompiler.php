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
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;

/**
 * @internal
 */
class RevisionCompiler implements CompilerInterface
{
    const REV_MANIFEST = 'rev-manifest.json';

    const EMPTY_REVISION = 'empty';

    /**
     * @var Filesystem
     */
    protected $assetsDir;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var callable[]
     */
    protected $sourcesCallbacks = [];

    /**
     * @param Filesystem $assetsDir
     * @param string $filename
     */
    public function __construct(Filesystem $assetsDir, string $filename)
    {
        $this->assetsDir = $assetsDir;
        $this->filename = $filename;
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

        $oldRevision = $this->getRevision();

        $newRevision = $this->calculateRevision($sources);

        // In case the previous and current revisions do not match
        // Or no file was written yet, let's save the file to disk.
        if ($force || $oldRevision !== $newRevision || ! $this->assetsDir->has($this->filename)) {
            if (! $this->save($this->filename, $sources)) {
                // If no file was written (because the sources were empty), we
                // will set the revision to a special value so that we can tell
                // that this file does not have a URL.
                $newRevision = static::EMPTY_REVISION;
            }

            $this->putRevision($newRevision);
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
        $revision = $this->getRevision();

        if (! $revision) {
            $this->commit();

            $revision = $this->getRevision();

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
     * @return string
     */
    protected function compile(array $sources): string
    {
        $output = '';

        foreach ($sources as $source) {
            $output .= $this->format($source->getContent());
        }

        return $output;
    }

    /**
     * @param string $string
     * @return string
     */
    protected function format(string $string): string
    {
        return $string;
    }

    protected function getRevision(): ?string
    {
        if ($this->assetsDir->has(static::REV_MANIFEST)) {
            $manifest = json_decode($this->assetsDir->read(static::REV_MANIFEST), true);

            return Arr::get($manifest, $this->filename);
        }

        return null;
    }

    protected function putRevision(?string $revision)
    {
        if ($this->assetsDir->has(static::REV_MANIFEST)) {
            $manifest = json_decode($this->assetsDir->read(static::REV_MANIFEST), true);
        } else {
            $manifest = [];
        }

        if ($revision) {
            $manifest[$this->filename] = $revision;
        } else {
            unset($manifest[$this->filename]);
        }

        $this->assetsDir->put(static::REV_MANIFEST, json_encode($manifest));
    }

    /**
     * @param SourceInterface[] $sources
     * @return string
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
        if ($this->getRevision() !== null) {
            $this->delete($this->filename);

            $this->putRevision(null);
        }
    }

    /**
     * @param string $file
     */
    protected function delete(string $file)
    {
        if ($this->assetsDir->has($file)) {
            $this->assetsDir->delete($file);
        }
    }
}
