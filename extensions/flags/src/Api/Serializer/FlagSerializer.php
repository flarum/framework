<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Flags\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Api\Serializer\BasicUserSerializer;
use Flarum\Api\Serializer\PostSerializer;
use Flarum\Flags\Flag;
use InvalidArgumentException;
use Tobscure\JsonApi\Relationship;

class FlagSerializer extends AbstractSerializer
{
    protected $type = 'flags';

    protected function getDefaultAttributes(object|array $model): array
    {
        if (! ($model instanceof Flag)) {
            throw new InvalidArgumentException(
                $this::class.' can only serialize instances of '.Flag::class
            );
        }

        return [
            'type'         => $model->type,
            'reason'       => $model->reason,
            'reasonDetail' => $model->reason_detail,
            'createdAt'    => $this->formatDate($model->created_at),
        ];
    }

    protected function post(Flag $flag): ?Relationship
    {
        return $this->hasOne($flag, PostSerializer::class);
    }

    protected function user(Flag $flag): ?Relationship
    {
        return $this->hasOne($flag, BasicUserSerializer::class);
    }
}
