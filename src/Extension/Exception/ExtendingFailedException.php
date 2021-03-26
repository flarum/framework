<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Extension\Exception;

use Exception;
use Flarum\Extension\Extension;
use Throwable;

class ExtendingFailedException extends Exception
{
    public $extension;
    public $extender;

    public function __construct(Extension $extension, $extender, Throwable $previous = null)
    {
        $this->extension = $extension;
        $this->extender = $extender;

        parent::__construct($extension->getTitle().' has an invalid extend.php: '.get_class($extender), null, $previous);
    }
}
