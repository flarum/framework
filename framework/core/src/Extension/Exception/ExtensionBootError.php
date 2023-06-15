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
    public function __construct(
        public Extension $extension,
        public object $extender,
        Throwable $previous = null
    ) {
        $extenderClass = get_class($extender);

        parent::__construct("Experienced an error while booting extension: {$extension->getTitle()}.\n\nError occurred while applying an extender of type: $extenderClass.", 0, $previous);
    }
}
