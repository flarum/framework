<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Admin;

use Flarum\Foundation\ValidationException;
use Flarum\Settings\Event\Serializing;
use Illuminate\Contracts\Events\Dispatcher;
use Less_Exception_Parser;
use Less_Parser;

class CheckCustomLessFormat
{
    public function subscribe(Dispatcher $events)
    {
        $events->listen(Serializing::class, [$this, 'check']);
    }

    public function check(Serializing $event)
    {
        if ($event->key === 'custom_less') {
            $parser = new Less_Parser();

            try {
                // Check the custom less format before saving
                // Variables names are not checked, we would have to set them and call getCss() to check them
                $parser->parse($event->value);
            } catch (Less_Exception_Parser $e) {
                throw new ValidationException([
                    'custom_less' => $e->getMessage(),
                ]);
            }
        }
    }
}
