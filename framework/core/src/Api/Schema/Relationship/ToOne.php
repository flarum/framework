<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Schema\Relationship;

use Flarum\Api\Schema\Concerns\FlarumRelationship;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Exception\BadRequestException;
use Tobyz\JsonApiServer\Exception\Sourceable;
use Tobyz\JsonApiServer\Laravel\Field\ToOne as BaseToOne;

class ToOne extends BaseToOne
{
    use FlarumRelationship;

    public function deserializeValue(mixed $value, Context $context): mixed
    {
        if ($this->deserializer) {
            return ($this->deserializer)($value, $context);
        }

        if (! is_array($value) || ! array_key_exists('data', $value)) {
            throw new BadRequestException('relationship does not include data key');
        }

        if ($value['data'] === null) {
            return null;
        }

        if (count($this->collections) === 1) {
            $value['data']['type'] ??= $this->collections[0];
        }

        try {
            return $this->findResourceForIdentifier($value['data'], $context);
        } catch (Sourceable $e) {
            throw $e->prependSource(['pointer' => '/data']);
        }
    }
}
