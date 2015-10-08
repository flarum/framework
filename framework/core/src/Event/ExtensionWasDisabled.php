<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Event;

class ExtensionWasDisabled
{
    /**
     * @var string
     */
    protected $extension;

    /**
     * @param string $extension
     */
    public function __construct($extension)
    {
        $this->extension = $extension;
    }
}
