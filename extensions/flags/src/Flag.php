<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Flags;

use Flarum\Core\Model;
use Flarum\Core\Support\VisibleScope;

class Flag extends Model
{
    use VisibleScope;

    protected $table = 'flags';

    protected $dates = ['time'];

    public function post()
    {
        return $this->belongsTo('Flarum\Core\Posts\Post');
    }

    public function user()
    {
        return $this->belongsTo('Flarum\Core\Users\User');
    }
}
