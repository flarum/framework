<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Serializer;

use stdClass;

class SystemInfoSerializer extends AbstractSerializer
{
    protected function getDefaultAttributes($data)
    {
        return [
            'content' => $data->content ?? ''
        ];
    }

    public function getId($data)
    {
        return 'system';
    }

    public function getType($data)
    {
        return 'system-info';
    }
}
