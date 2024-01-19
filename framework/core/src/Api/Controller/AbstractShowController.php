<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Tobscure\JsonApi\Resource;
use Tobscure\JsonApi\SerializerInterface;

abstract class AbstractShowController extends AbstractSerializeController
{
    protected function createElement(mixed $data, SerializerInterface $serializer): \Tobscure\JsonApi\ElementInterface
    {
        return new Resource($data, $serializer);
    }
}
