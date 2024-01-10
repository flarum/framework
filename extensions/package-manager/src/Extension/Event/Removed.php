<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Extension\Event;

use Flarum\Extension\Extension;

class Removed
{
    /**
     * @var Extension
     */
    public $extension;

    public function __construct(Extension $extension)
    {
        $this->extension = $extension;
    }
}
