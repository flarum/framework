<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend\Asset;

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
     * @param string $file
     */
    public function addFile(string $file);

    /**
     * @param callable $callback
     */
    public function addString(callable $callback);

    /**
     * @return string|null
     */
    public function getUrl(): ?string;

    public function flush();
}
