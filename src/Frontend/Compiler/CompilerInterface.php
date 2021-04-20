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
    public function getName(): string;

    /**
     * @param string $name
     */
    public function setName(string $name);

    /**
     * @param callable $callback
     */
    public function addSources(callable $callback);

    public function commit();

    /**
     * @return array
     */
    public function getUrls(): array;

    public function flush();
}
