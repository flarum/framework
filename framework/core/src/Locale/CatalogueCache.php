<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Locale;

use Symfony\Component\Config\ConfigCacheInterface;

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
 * The revision recorded beside a catalogue is compared against the current one
 * on every request for that locale. It is derived from what the instance can
 * see for itself, so an instance that was never told anything still works out
 * that its catalogue is stale and rebuilds it.
 *
 * Freshness is decided per locale, because Symfony compiles catalogues lazily
 * per locale: a request for `de` never touches `en`. A locale nobody asks for
 * therefore costs nothing, and rebuilds the moment it is first requested —
 * where previously it would have stayed stale indefinitely.
 *
 * Registered resources are never asked whether they are fresh. Doing so would
 * run third-party {@see \Symfony\Component\Config\Resource\SelfCheckingResourceInterface}
 * implementations that have never executed outside debug mode, some of which
 * resolve services and query the database on every request.
 */
class CatalogueCache implements ConfigCacheInterface
{
    public function __construct(
        private readonly string $file,
        private readonly string $revision
    ) {
    }

    public function getPath(): string
    {
        return $this->file;
    }

    public function isFresh(): bool
    {
        if (! is_file($this->file)) {
            return false;
        }

        return @file_get_contents($this->revisionPath()) === $this->revision;
    }

    public function write(string $content, ?array $metadata = null): void
    {
        // The catalogue first, then its revision: a process that dies between
        // the two leaves a catalogue whose revision does not match, so the next
        // request rebuilds it. Writing the revision first would mark a
        // catalogue that was never written as current.
        file_put_contents($this->file, $content);
        file_put_contents($this->revisionPath(), $this->revision);
    }

    /**
     * Beside the catalogue, and swept by the same `storage/locale/*` glob that
     * clears it.
     */
    private function revisionPath(): string
    {
        return $this->file.'.revision';
    }
}
