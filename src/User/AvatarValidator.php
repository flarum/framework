<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Foundation\AbstractValidator;

class AvatarValidator extends AbstractValidator
{
    protected $rules = [
        'avatar' => [
            'required',
            'mimes:jpeg,png,bmp,gif',
            'max:2048'
        ]
    ];
}
