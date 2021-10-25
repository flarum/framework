<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler\Source;

use InvalidArgumentException;

/**
 * @internal
 */
class FileSource implements SourceInterface
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $extensionId;

    /**
     * @param string $path
     */
    public function __construct(string $path, ?string $extensionId = null)
    {
        if (! file_exists($path)) {
            throw new InvalidArgumentException("File not found at path: $path");
        }

        $this->path = $path;
        $this->extensionId = $extensionId;
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

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getExtensionId(): ?string
    {
        return $this->extensionId;
    }
}
