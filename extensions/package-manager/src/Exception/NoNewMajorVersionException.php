<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Exception;

use Exception;
use Flarum\Foundation\KnownError;

class NoNewMajorVersionException extends Exception implements KnownError
{
    public function __construct()
    {
        parent::__construct('No new major version known of. Try checking for updates first.');
    }

    public function getType(): string
    {
        return 'no_new_major_version';
    }
}
