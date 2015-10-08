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

class PostValidator extends AbstractValidator
{
    protected $rules = [
        'discussion_id' => ['required', 'integer'],
        'time' => ['required', 'date'],
        'content' => ['required', 'max:65535'],
        'number' => ['integer'],
        'user_id' => ['integer'],
        'edit_time' => ['date'],
        'edit_user_id' => ['integer'],
        'hide_time' => ['date'],
        'hide_user_id' => ['integer']
    ];
}
