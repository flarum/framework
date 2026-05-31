<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler;

use Illuminate\Contracts\Filesystem\Filesystem;

class FileVersioner implements VersionerInterface
{
    public const REV_MANIFEST = 'rev-manifest.json';

    private ?array $cachedManifest = null;

    public function __construct(
        protected Filesystem $filesystem
    ) {
    }

    public function putRevision(string $file, ?string $revision): void
    {
        $manifest = $this->readManifest();

        if ($revision) {
            $manifest[$file] = $revision;
        } else {
            unset($manifest[$file]);
        }

        $this->filesystem->put(static::REV_MANIFEST, json_encode($manifest));
        $this->cachedManifest = $manifest;
    }

    public function getRevision(string $file): ?string
    {
        return $this->readManifest()[$file] ?? null;
    }

    public function allRevisions(): array
    {
        return $this->readManifest();
    }

    /**
     * Read the manifest, caching the parsed array for the lifetime of this
     * instance. Frontend rendering constructs ~6 compilers per request, all
     * sharing the same VersionerInterface singleton — without this cache the
     * manifest is read and JSON-decoded once per compiler.
     */
    private function readManifest(): array
    {
        if ($this->cachedManifest !== null) {
            return $this->cachedManifest;
        }

        if (! $this->filesystem->exists(static::REV_MANIFEST)) {
            return $this->cachedManifest = [];
        }

        $contents = $this->filesystem->get(static::REV_MANIFEST);
        $manifest = json_decode($contents, true);

        return $this->cachedManifest = is_array($manifest) ? $manifest : [];
    }
}
