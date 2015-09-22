<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Flags\Api;

use Flarum\Api\Serializers\Serializer;

class FlagSerializer extends Serializer
{
    protected $type = 'flags';

    protected function getDefaultAttributes($flag)
    {
        return [
            'type'         => $flag->type,
            'reason'       => $flag->reason,
            'reasonDetail' => $flag->reason_detail,
        ];
    }

    protected function post()
    {
        return $this->hasOne('Flarum\Api\Serializers\PostSerializer');
    }

    protected function user()
    {
        return $this->hasOne('Flarum\Api\Serializers\UserBasicSerializer');
    }
}
