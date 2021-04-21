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

    protected $ignoredDirectories = [
        'fonts'
    ];

    /**
     * @var Filesystem
     */
    protected $assetsDir;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $type = '';

    /**
     * @var callable[]
     */
    protected $sourcesCallbacks = [];

    /**
     * @param Filesystem $assetsDir
     * @param string $name
     * @param string $type
     */
    public function __construct(Filesystem $assetsDir, string $name, string $type)
    {
        $this->assetsDir = $assetsDir;
        $this->name = $name;
        $this->type = $type;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        $sources = $this->getSources();

        $this->save($this->name, $sources);
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
    public function getUrls(): array
    {
        $revision = $this->getRevision();

        if (! $revision) {
            $this->commit();

            $revision = $this->getRevision();

            if (empty($revision)) {
                return [];
            }
        }

        if ($revision === static::EMPTY_REVISION) {
            return [];
        }

        $manifest = json_decode($this->assetsDir->read(static::REV_MANIFEST), true);

        $appResources = array_merge(Arr::get($manifest, $this->name));

        $urls = [];

        if (! empty($appResources[$this->type])) {
            foreach ($appResources[$this->type] as $revision => $target) {
                $ext = pathinfo($target, PATHINFO_EXTENSION);
                $file = substr_replace($target, '-'.$revision, -strlen($ext) - 1, 0);
                $urls[] = $this->assetsDir->url($file);
            }
        }

        return $urls;
    }

    /**
     * @param string $file
     * @param SourceInterface[] $sources
     * @return bool true if the file was written, false if there was nothing to write
     */
    protected function save(string $file, array $sources): bool
    {
        if ($content = $this->compile($sources)) {
            $this->putFile($this->name.'/'.$file.'.'.$this->type, hash('crc32b', serialize([$content])), $content);

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
     * @return array
     */
    protected function getRevision(): array
    {
        if ($this->assetsDir->has(static::REV_MANIFEST)) {
            $manifest = json_decode($this->assetsDir->read(static::REV_MANIFEST), true);

            return (array) Arr::get($manifest, $this->name);
        }

        return [];
    }

    /**
     * @param string $location
     * @param $cacheDifferentiator
     * @param $content
     */
    protected function putFile(string $location, $cacheDifferentiator, $content)
    {
        if ($this->assetsDir->has(static::REV_MANIFEST)) {
            $manifest = json_decode($this->assetsDir->read(static::REV_MANIFEST), true);
        } else {
            $manifest = [];
        }

        if (! isset($manifest[$this->name])) {
            $manifest[$this->name] = [];
        }

        if (! isset($manifest[$this->name][$this->type])) {
            $manifest[$this->name][$this->type] = [];
        }

        if ($location) {
            $ext = pathinfo($location, PATHINFO_EXTENSION);

            $filename = substr_replace($location, '-'.$cacheDifferentiator, -strlen($ext) - 1, 0);

            $this->assetsDir->put($filename, $content);
            $manifest[$this->name][$this->type][$cacheDifferentiator] = $location;
        } else {
            unset($manifest[$this->name][$this->type]);
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
        $path = $this->assetsDir->getAdapter()->getPathPrefix();
        $files = array_diff(scandir($path), ['.', '..']);

        foreach ($files as $file) {
            if (! in_array($file, $this->ignoredItems)) {
                $filePath = $this->assetsDir->getAdapter()->getPathPrefix().'/'.$file;
                if (is_dir($filePath)) {
                    $this->assetsDir->deleteDirectory($file);
                } else {
                    $this->assetsDir->delete($file);
                }
            }
        }
    }
}
