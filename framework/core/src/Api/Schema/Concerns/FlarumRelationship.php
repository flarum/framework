<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Schema\Concerns;

trait FlarumRelationship
{
    use FlarumField;

    public ?string $inverse = null;

    /**
     * Whether endpoints apply the related resource's scope() when they eager
     * load this relationship. See withoutRelatedScope().
     */
    public bool $relatedScope = true;

    /**
     * Have endpoints eager load this relationship in full, without the related
     * resource's scope().
     *
     * An endpoint applies the related resource's scope() to the relationships
     * it eager loads, so that only what the actor may see is serialized. But
     * the relation it loads is the model's own, and other code reads it too:
     * a user's `groups`, for one, decide whether they are an admin and what
     * they may do, and the policies that ask have to see every one of them,
     * not only the ones the actor may see. Use this for a relationship that
     * policies or other logic need to read in full.
     *
     * The field must then leave out what the actor may not see in its get()
     * callback. With one, the serializer never loads the relationship itself,
     * so endpoint eager loads are the only place this applies. Constraints
     * given to eagerLoadWhere() still apply, and the rest of a path beyond
     * this relationship is scoped as usual.
     */
    public function withoutRelatedScope(): static
    {
        $this->relatedScope = false;

        return $this;
    }

    /**
     * Set the inverse relationship name, used for eager loading.
     */
    public function inverse(string $inverse): static
    {
        $this->inverse = $inverse;

        return $this;
    }

    /**
     * Allow this relationship to be included.
     */
    public function includable(bool $includable = true): static
    {
        $this->includable = $includable;

        return $this;
    }
}
