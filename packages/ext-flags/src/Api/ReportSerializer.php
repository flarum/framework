<?php 
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Reports\Api;

use Flarum\Api\Serializers\Serializer;

class ReportSerializer extends Serializer
{
    protected $type = 'reports';

    protected function getDefaultAttributes($report)
    {
        return [
            'reporter'      => $report->reporter,
            'reason'        => $report->reason,
            'reasonDetail'  => $report->reason_detail,
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
