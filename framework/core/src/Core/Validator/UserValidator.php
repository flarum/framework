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
        'username' => ['required', 'alpha_dash', 'unique', 'min:3', 'max:30'],
        'email' => ['required', 'email', 'unique'],
        'password' => ['required'],
        'join_time' => ['date'],
        'last_seen_time' => ['date'],
        'discussions_count' => ['integer'],
        'posts_count' => ['integer']
    ];
}
