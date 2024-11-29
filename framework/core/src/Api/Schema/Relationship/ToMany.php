<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Schema\Relationship;

use Flarum\Api\Schema\Concerns\FlarumRelationship;
use InvalidArgumentException;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Laravel\Field\ToMany as BaseToMany;

class ToMany extends BaseToMany
{
    use FlarumRelationship;

    public function serializeValue(mixed $value, Context $context): mixed
    {
        if ($value && ! is_array($value) && method_exists($value, 'toArray')) {
            $value = $value->toArray();
        } elseif ($value && ! is_array($value)) {
            throw new InvalidArgumentException(sprintf('Relationship value [%s] must be an array', $this->name));
        }

        return parent::serializeValue($value, $context);
    }
}
