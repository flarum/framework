<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Core\Validator;

class UserValidator extends AbstractValidator
{
    protected $rules = [
        'username' => [
            'required',
            'alpha_dash',
            'unique:users',
            'min:3',
            'max:30'
        ],
        'email' => [
            'required',
            'email',
            'unique:users'
        ],
        'password' => [
            'required',
            'min:8'
        ]
    ];
}
