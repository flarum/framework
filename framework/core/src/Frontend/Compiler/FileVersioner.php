<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;

class FileVersioner implements VersionerInterface
{
    const REV_MANIFEST = 'rev-manifest.json';

    public function __construct(
        protected Filesystem $filesystem
    ) {
    }

    public function putRevision(string $file, ?string $revision): void
    {
        if ($this->filesystem->exists(static::REV_MANIFEST)) {
            $manifest = json_decode($this->filesystem->get(static::REV_MANIFEST), true);
        } else {
            $manifest = [];
        }

        if ($revision) {
            $manifest[$file] = $revision;
        } else {
            unset($manifest[$file]);
        }

        $this->filesystem->put(static::REV_MANIFEST, json_encode($manifest));
    }

    public function getRevision(string $file): ?string
    {
        if ($this->filesystem->exists(static::REV_MANIFEST)) {
            $manifest = json_decode($this->filesystem->get(static::REV_MANIFEST), true);

            return Arr::get($manifest, $file);
        }

        return null;
    }

    public function allRevisions(): array
    {
        if ($contents = $this->filesystem->get(static::REV_MANIFEST)) {
            return json_decode($contents, true);
        }

        return [];
    }
}
