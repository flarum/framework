<?php

namespace Flarum\Http\Exception;

use Exception;
use Flarum\Foundation\KnownError;

class MaintenanceModeException extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'maintenance';
    }
}
