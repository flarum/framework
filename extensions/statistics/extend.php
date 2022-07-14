<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

use Flarum\Extend;

return [
    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),
    (new Extend\Routes('api'))
        ->get('/statistics', 'flarum-statistics.get-statistics', Flarum\Statistics\Api\Controller\ShowStatisticsData::class),
];
