<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler;

/**
 * @internal
 */
class JsChunkCompiler extends JsCompiler
{
    public function getUrl(): ?string
    {
        $revision = $this->versioner->getRevision($this->filename);

        // Legacy CRC32 revisions describe unversioned files, which must be republished.
        if ($revision !== null && $revision !== static::EMPTY_REVISION && strlen($revision) !== 32) {
            $this->commit();
        }

        return parent::getUrl();
    }

    protected function hashOutput(string $content): string
    {
        // Fit asset_revisions.revision while retaining 128 bits for content addressing.
        return substr(hash('sha256', $content), 0, 32);
    }

    protected function outputFilename(string $revision): string
    {
        return substr($this->filename, 0, -3).".$revision.js";
    }

    protected function renderOutput(array $sources): ?string
    {
        $output = parent::renderOutput($sources);

        if ($output === null) {
            return null;
        }

        // Hash the map independently to avoid a cycle between its URL and the JS hash.
        $mapFile = $this->filename.'.map';
        $map = $this->pendingSidecars[$mapFile];
        $versionedMap = substr($this->filename, 0, -3).'.'.$this->hashOutput($map).'.js.map';
        $this->pendingSidecars = [$versionedMap => $map];

        return str_replace(
            '//# sourceMappingURL='.$this->assetsDir->url($mapFile),
            '//# sourceMappingURL='.$this->assetsDir->url($versionedMap),
            $output
        );
    }
}
