<?php

namespace Flarum\ExtensionManager\Exception;

use Exception;
use Flarum\Foundation\KnownError;

class CannotFetchExternalExtension extends Exception implements KnownError
{
    public function getType(): string
    {
        return 'cannot_fetch_external_extension';
    }
}
