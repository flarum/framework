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

    /**
     * {@inheritdoc}
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * {@inheritdoc}
     */
    public function setFilename(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        $sources = $this->getSources();

        $oldRevision = $this->getRevision();

        $newRevision = $this->calculateRevision($sources);

        $oldFile = $oldRevision ? $this->getFilenameForRevision($oldRevision) : null;

        if ($oldRevision !== $newRevision || ($oldFile && ! $this->assetsDir->has($oldFile))) {
            $newFile = $this->getFilenameForRevision($newRevision);

            if (! $this->save($newFile, $sources)) {
                // If no file was written (because the sources were empty), we
                // will set the revision to a special value so that we can tell
                // that this file does not have a URL.
                $newRevision = static::EMPTY_REVISION;
            }

            $this->putRevision($newRevision);

            if ($oldFile) {
                $this->delete($oldFile);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addSources(callable $callback)
    {
        $this->sourcesCallbacks[] = $callback;
    }

    /**
     * @return SourceInterface[]
     */
    protected function getSources()
    {
        $sources = new SourceCollector;

        foreach ($this->sourcesCallbacks as $callback) {
            $callback($sources);
        }

        return $sources->getSources();
    }

    /**
     * {@inheritdoc}
     */
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

        $file = $this->getFilenameForRevision($revision);

        return $this->assetsDir->url($file);
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

    /**
     * Get the filename for the given revision.
     *
     * @param string $revision
     * @return string
     */
    protected function getFilenameForRevision(string $revision): string
    {
        $ext = pathinfo($this->filename, PATHINFO_EXTENSION);

        return substr_replace($this->filename, '-'.$revision, -strlen($ext) - 1, 0);
    }

    /**
     * @return string|null
     */
    protected function getRevision(): ?string
    {
        if ($this->assetsDir->has(static::REV_MANIFEST)) {
            $manifest = json_decode($this->assetsDir->read(static::REV_MANIFEST), true);

            return Arr::get($manifest, $this->filename);
        }

        return null;
    }

    /**
     * @param string|null $revision
     */
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

    /**
     * @return mixed
     */
    protected function getCacheDifferentiator()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        if ($revision = $this->getRevision()) {
            $file = $this->getFilenameForRevision($revision);

            $this->delete($file);

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
