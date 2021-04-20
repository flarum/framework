<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler\Source;

use InvalidArgumentException;

class FileSource implements SourceInterface
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
        if (! file_exists($path)) {
            throw new InvalidArgumentException("File not found at path: $path");
        }

        $this->path = $path;
        $this->moduleName = $moduleName;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return file_get_contents($this->path);
    }

    /**
     * @return mixed
     */
    public function getCacheDifferentiator()
    {
        return [$this->path, filemtime($this->path)];
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    public function getFilename(): string
    {
        return pathinfo($this->path)['basename'];
    }

    public function getModuleName(): string
    {
        return $this->moduleName ?: 'core';
    }
}
