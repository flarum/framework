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

class ExtensionBootError extends Exception
{
    public $extension;
    public $extender;

    public function __construct(Extension $extension, $extender, Throwable $previous = null)
    {
        $this->extension = $extension;
        $this->extender = $extender;

        $extenderClass = get_class($extender);

        parent::__construct("Experienced an error while booting extension: {$extension->getTitle()}.\n\nError occurred while applying an extender of type: $extenderClass.", null, $previous);
    }
}
