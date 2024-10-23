<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings\Event;

class Serializing
{
    public function __construct(
        /**
         * The settings key being saved.
         */
        public string $key,
        /**
         * The settings value to save.
         */
        public string &$value
    ) {
    }
}
