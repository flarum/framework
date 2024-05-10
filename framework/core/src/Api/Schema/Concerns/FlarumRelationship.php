<?php

namespace Flarum\Api\Schema\Concerns;

trait FlarumRelationship
{
    use FlarumField;

    public ?string $inverse = null;

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
