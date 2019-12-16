<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler\Source;

interface SourceInterface
{
    /**
     * @return string
     */
    public function getContent(): string;

    /**
     * @return mixed
     */
    public function getCacheDifferentiator();
}
