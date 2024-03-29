<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings\Event;

/**
 * Prepare settings for display in the client.
 *
 * This event is fired when settings have been retrieved from the database and
 * are being unserialized for display in the client.
 */
class Deserializing
{
    public function __construct(
        /**
         * The settings array to be unserialized.
         */
        public array &$settings
    ) {
    }
}
