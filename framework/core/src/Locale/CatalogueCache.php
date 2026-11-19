<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\Resource\SelfCheckingResourceChecker;
use Symfony\Component\Config\ResourceCheckerConfigCache;

/**
 * Decides whether a compiled catalogue still reflects the translations this
 * instance has been given.
 *
 * Symfony names a catalogue after its fallback locales alone, and outside debug
 * mode considers it fresh if the file simply exists — so once written, nothing
 * detects that the translations behind it changed. Enabling an extension on one
 * instance leaves every other instance serving a catalogue that predates it,
 * with no mtime, TTL or revision that would ever say otherwise.
 *
 * So each revision gets its own catalogue, named after it. The revision is
 * derived from what the instance can see for itself, so an instance that was
 * never told anything still looks for a catalogue that does not exist yet, and
 * builds it. Because the name carries the revision, a catalogue cannot be
 * paired with a revision it was not built for, instances on different
 * revisions (old and new servers during a deploy) each keep their own, and no
 * opcache anywhere holds an older compile under that name. Catalogues of
 * earlier revisions stay until the cache is cleared, which every change of
 * revision in core comes with.
 *
 * Freshness is decided per locale, because Symfony compiles catalogues lazily
 * per locale: a request for `de` never touches `en`. A locale nobody asks for
 * therefore costs nothing, and rebuilds the moment it is first requested —
 * where previously it would have stayed stale indefinitely.
 *
 * Outside debug mode, registered resources are never asked whether they are
 * fresh. Doing so would run third-party {@see \Symfony\Component\Config\Resource\SelfCheckingResourceInterface}
 * implementations that have never executed outside debug mode, some of which
 * resolve services and query the database on every request. In debug mode they
 * are asked, as Symfony's own cache did, so an edited translation file shows
 * on the next request.
 */
class CatalogueCache implements ConfigCacheInterface
{
    private readonly string $path;

    /**
     * @param string $file The path Symfony names the catalogue by.
     */
    public function __construct(
        string $file,
        string $revision,
        private readonly bool $debug = false
    ) {
        $this->path = preg_replace('/\.php$/', '', $file).'.'.hash('xxh128', $revision).'.php';
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isFresh(): bool
    {
        if (! is_file($this->path)) {
            return false;
        }

        return ! $this->debug || $this->resourceCache()->isFresh();
    }

    public function write(string $content, ?array $metadata = null): void
    {
        // A failed write is a cache miss, not an error: the translator already
        // holds this catalogue in memory, and the next request writes it again.
        // A cache clear can delete another request's temporary file between its
        // write and its rename, so failures do happen on busy forums.
        $directory = dirname($this->path);

        if (! is_dir($directory) && ! @mkdir($directory, 0775, true) && ! is_dir($directory)) {
            return;
        }

        // Before the catalogue, so a catalogue always has the resources it was
        // built from beside it.
        if ($this->debug && ! $this->dump($this->metaPath(), serialize($metadata ?? []))) {
            return;
        }

        if (! $this->dump($this->path, $content)) {
            return;
        }

        // In case this exact catalogue was compiled before: it was cleared and
        // rebuilt, or a translation file was edited in debug mode.
        if (function_exists('opcache_invalidate') && filter_var(ini_get('opcache.enable'), FILTER_VALIDATE_BOOL)) {
            @opcache_invalidate($this->path, true);
        }
    }

    /**
     * Written beside the target and renamed over it, so a reader sees the old
     * file or the new one, never part of one.
     */
    private function dump(string $path, string $content): bool
    {
        $temporary = $path.'.'.bin2hex(random_bytes(6)).'.tmp';

        if (@file_put_contents($temporary, $content) !== false && @rename($temporary, $path)) {
            return true;
        }

        @unlink($temporary);

        return false;
    }

    /**
     * Beside the catalogue, and swept by the same `storage/locale/*` glob that
     * clears it.
     */
    private function metaPath(): string
    {
        return $this->path.'.meta';
    }

    /**
     * Symfony's check of the resources recorded beside the catalogue against
     * its modification time.
     */
    private function resourceCache(): ResourceCheckerConfigCache
    {
        return new ResourceCheckerConfigCache($this->path, [new SelfCheckingResourceChecker()], $this->metaPath());
    }
}
