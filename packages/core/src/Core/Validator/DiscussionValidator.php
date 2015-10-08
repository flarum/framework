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

class DiscussionValidator extends AbstractValidator
{
    protected $rules = [
        'title' => ['required', 'max:80'],
        'start_time' => ['required', 'date'],
        'comments_count' => ['integer'],
        'participants_count' => ['integer'],
        'start_user_id' => ['integer'],
        'start_post_id' => ['integer'],
        'last_time' => ['date'],
        'last_user_id' => ['integer'],
        'last_post_id' => ['integer'],
        'last_post_number' => ['integer'],
    ];
}
