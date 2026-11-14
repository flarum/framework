<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Exception;

use Exception;
use Flarum\Foundation\KnownError;

class PrecognitionNotSupportedException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'precognition_not_supported';
    }
}
