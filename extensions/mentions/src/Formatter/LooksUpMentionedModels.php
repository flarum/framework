<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mentions\Formatter;

use Flarum\Database\AbstractModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use s9e\TextFormatter\Utils;

trait LooksUpMentionedModels
{
    /**
     * The models that the given mention tags in the XML refer to.
     *
     * The context's relation is used when it has been loaded. Otherwise every
     * mention in the XML is looked up in one query, rather than one each, since
     * extensions render posts without loading what their mentions need.
     */
    protected function mentionedModels(mixed $context, string $relation, string $xml, string $tagName, Builder $query): Collection
    {
        if ($context instanceof AbstractModel && $context->relationLoaded($relation)) {
            return $context->getRelation($relation);
        }

        $ids = array_unique(Utils::getAttributeValues($xml, $tagName, 'id'));

        return $ids ? $query->findMany($ids) : new Collection();
    }
}
