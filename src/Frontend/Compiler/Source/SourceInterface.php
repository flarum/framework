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
     * @return string|array
     */
    public function getContent();

    /**
     * @return mixed
     */
    public function getCacheDifferentiator();
}
