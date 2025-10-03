<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

trait ValidatesMailSettings
{
    /**
     * Returns a validation rule that checks for leading or trailing whitespace.
     *
     * @return callable
     */
    protected function noWhitespace(): callable
    {
        return function ($attribute, $value, $fail) {
            if ($value !== trim($value)) {
                $fail('The ' . str_replace('_', ' ', $attribute) . ' must not contain leading or trailing whitespace.');
            }
        };
    }
}
