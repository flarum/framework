<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PackageManager\Api\Serializer;

use Flarum\Api\Serializer\AbstractSerializer;
use Flarum\Extension\Extension;
use InvalidArgumentException;

class ExtensionSerializer extends AbstractSerializer
{
    protected $type = 'extensions';

    public function getId($model)
    {
        return is_array($model) ? $model['id'] : $model->getId();
    }

    protected function getDefaultAttributes($model)
    {
        if (is_array($model)) {
            return $model;
        }

        if (! ($model instanceof Extension)) {
            throw new InvalidArgumentException(
                get_class($this).' can only serialize instances of '.Extension::class
            );
        }

        return $model->toArray();
    }
}
