<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend\Asset;

use Illuminate\Filesystem\FilesystemAdapter;

class RevisionCompiler implements CompilerInterface
{
    const REV_MANIFEST = 'rev-manifest.json';

    /**
     * @var FilesystemAdapter
     */
    protected $assetsDir;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var bool
     */
    protected $watch;

    /**
     * @var array
     */
    protected $content = [];

    /**
     * @param FilesystemAdapter $assetsDir
     * @param string $filename
     * @param bool $watch
     */
    public function __construct(FilesystemAdapter $assetsDir, string $filename, bool $watch = false)
    {
        $this->assetsDir = $assetsDir;
        $this->filename = $filename;
        $this->watch = $watch;
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
    public function addFile(string $file)
    {
        $this->content[] = $file;
    }

    /**
     * {@inheritdoc}
     */
    public function addString(callable $callback)
    {
        $this->content[] = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl(): ?string
    {
        $oldRevision = $currentRevision = $this->getRevision();

        if (! $oldRevision || $this->watch) {
            $currentRevision = $this->calculateRevision();
        }

        $file = $oldFile = $oldRevision ? $this->getFilenameForRevision($oldRevision) : null;

        if ($oldRevision !== $currentRevision || ($oldFile && ! $this->assetsDir->has($oldFile))) {
            $file = $this->getFilenameForRevision($currentRevision);

            if (! $this->save($file)) {
                return null;
            }

            $this->putRevision($currentRevision);

            if ($oldFile) {
                $this->delete($oldFile);
            }
        }

        return $this->assetsDir->url($file);
    }

    /**
     * @param string $file
     * @return bool true if the file was written, false if there was nothing to write
     */
    protected function save(string $file): bool
    {
        if ($content = $this->compile()) {
            $this->assetsDir->put($file, $content);

            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    protected function compile(): string
    {
        $output = '';

        foreach ($this->content as $source) {
            if (is_callable($source)) {
                $content = $source();
            } else {
                $content = file_get_contents($source);
            }

            $output .= $this->format($content);
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

            return array_get($manifest, $this->filename);
        }
    }

    /**
     * @param string $revision
     * @return int
     */
    protected function putRevision(string $revision)
    {
        if ($this->assetsDir->has(static::REV_MANIFEST)) {
            $manifest = json_decode($this->assetsDir->read(static::REV_MANIFEST), true);
        } else {
            $manifest = [];
        }

        $manifest[$this->filename] = $revision;

        $this->assetsDir->put(static::REV_MANIFEST, json_encode($manifest));
    }

    /**
     * @return string
     */
    protected function calculateRevision(): string
    {
        $cacheDifferentiator = [$this->getCacheDifferentiator()];

        foreach ($this->content as $source) {
            if (is_callable($source)) {
                $cacheDifferentiator[] = $source();
            } else {
                $cacheDifferentiator[] = [$source, filemtime($source)];
            }
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
        $revision = $this->getRevision();

        $file = $this->getFilenameForRevision($revision);

        $this->delete($file);
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
