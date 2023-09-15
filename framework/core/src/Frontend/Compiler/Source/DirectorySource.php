<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler\Source;

use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;

class DirectorySource implements SourceInterface
{
    protected FilesystemAdapter $filesystem;

    public function __construct(
        protected string $path,
        protected ?string $extensionId = null
    ) {
        $this->filesystem = new FilesystemAdapter(
            new Filesystem($adapter = new LocalFilesystemAdapter($path)),
            $adapter,
            ['root' => $path]
        );
    }

    public function getContent(): string
    {
        return '';
    }

    public function getCacheDifferentiator(): array
    {
        return [$this->path, filemtime($this->path)];
    }

    public function getFilesystem(): FilesystemAdapter
    {
        return $this->filesystem;
    }

    public function getExtensionId(): ?string
    {
        return $this->extensionId;
    }
}
