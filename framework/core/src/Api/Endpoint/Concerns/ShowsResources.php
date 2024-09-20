<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Endpoint\Concerns;

use Flarum\Api\Serializer;
use Tobyz\JsonApiServer\Context;
use Tobyz\JsonApiServer\Endpoint\Concerns\IncludesData;
use Tobyz\JsonApiServer\Schema\Concerns\HasMeta;

trait ShowsResources
{
    use HasMeta;
    use IncludesData;

    protected function showResource(Context $context, mixed $model): array
    {
        $serializer = new Serializer($context);

        $serializer->addPrimary(
            $context->resource($context->collection->resource($model, $context)),
            $model,
            $this->getInclude($context),
        );

        [$primary, $included] = $serializer->serialize();

        $document = ['data' => $primary[0]];

        if (count($included)) {
            $document['included'] = $included;
        }

        if ($meta = $this->serializeMeta($context)) {
            $document['meta'] = $meta;
        }

        return $document;
    }
}
