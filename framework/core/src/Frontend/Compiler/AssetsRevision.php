<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler;

/**
 * Produces a single token representing the current state of all compiled asset
 * revisions, so a long-lived client can detect when the assets it booted with
 * have been superseded (e.g. after a rebuild or extension toggle).
 *
 * The token is derived solely from {@see VersionerInterface::allRevisions()}, so
 * it honours whatever versioner is bound — including a custom one. The same
 * computation must be reproducible on the client from the `revisions` payload it
 * boots with, so the manifest is canonicalised (sorted by key) before hashing.
 */
class AssetsRevision
{
    public function __construct(
        protected VersionerInterface $versioner
    ) {
    }

    public function token(): string
    {
        $revisions = $this->versioner->allRevisions();

        return self::tokenFor($revisions);
    }

    /**
     * Canonicalise a revisions map (sort by key) and hash it, so the server and
     * client produce the same token from the same manifest regardless of order.
     *
     * Admin-only assets are excluded: the token exists to tell a *forum* client
     * that what it loaded has been superseded, but the manifest is shared with
     * the admin frontend. Without this, rebuilding any admin asset — e.g.
     * toggling an admin-only extension — would move the token and prompt every
     * forum visitor to reload for a change that cannot affect them. Forum and
     * shared (`common`) chunks stay in: the forum can load those, so a genuine
     * change to one must still trigger the prompt.
     *
     * @param array<string, string|null> $revisions
     */
    public static function tokenFor(array $revisions): string
    {
        $revisions = array_filter(
            $revisions,
            fn (string $key) => ! self::isAdminAsset($key),
            ARRAY_FILTER_USE_KEY
        );

        ksort($revisions);

        return hash('xxh128', (string) json_encode($revisions));
    }

    /**
     * Whether a manifest key names an asset only the admin frontend loads: the
     * admin entry bundles (`admin.js`/`admin.css` and their locale variants)
     * and admin code-split chunks (`js/<ext>/admin/…`).
     */
    private static function isAdminAsset(string $key): bool
    {
        return preg_match('~^admin(-[^.]+)?\.(js|css)$~', $key) === 1
            || str_contains($key, '/admin/');
    }
}
