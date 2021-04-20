<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler\Source;

use InvalidArgumentException;

class FolderSource implements SourceInterface
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var string|null
     */
    protected $moduleName;

    /**
     * @param string $path
     * @param string|null $moduleName
     */
    public function __construct(string $path, ?string $moduleName)
    {
        if (! is_dir($path)) {
            throw new InvalidArgumentException("Folder not found at path: $path");
        }

        $this->path = $path;
        $this->moduleName = $moduleName;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    public function getDirectoryName(): string
    {
        return pathinfo($this->path)['basename'];
    }

    public function getContent(): array
    {
        return array_diff(scandir($this->path), ['.', '..']);
    }

    /**
     * @return mixed
     */
    public function getCacheDifferentiator()
    {
        return [$this->path, filemtime($this->path)];
    }

    public function getModuleName(): string
    {
        return $this->moduleName ?: 'core';
    }
}
