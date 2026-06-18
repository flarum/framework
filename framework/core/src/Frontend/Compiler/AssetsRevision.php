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
     * @param array<string, string|null> $revisions
     */
    public static function tokenFor(array $revisions): string
    {
        ksort($revisions);

        return hash('xxh128', (string) json_encode($revisions));
    }
}
