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
    /**
     * @return string
     */
    public function getFilename(): string;

    /**
     * @param string $filename
     */
    public function setFilename(string $filename);

    /**
     * @param callable $callback
     */
    public function addSources(callable $callback);

    public function commit();

    /**
     * @return string|null
     */
    public function getUrl(): ?string;

    public function flush();
}
