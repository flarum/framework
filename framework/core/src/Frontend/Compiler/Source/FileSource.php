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
    public function __construct(
        protected string $path,
        protected ?string $extensionId = null
    ) {
        if (! file_exists($path)) {
            throw new InvalidArgumentException("File not found at path: $path");
        }
    }

    public function getContent(): string
    {
        return file_get_contents($this->path);
    }

    public function getCacheDifferentiator(): array
    {
        return [$this->path, filemtime($this->path)];
    }

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
