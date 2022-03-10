<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler;

interface CompilerInterface
{
    public function getFilename(): string;

    public function setFilename(string $filename);

    public function addSources(callable $callback);

    public function commit(bool $force = false);

    public function getUrl(): ?string;

    public function flush();
}
