<?php

namespace Flarum\Foundation\Concerns;

use Flarum\Foundation\SiteInterface;

trait Extending
{
    /**
     * @var \Flarum\Extend\ExtenderInterface[]
     */
    protected $extenders = [];

    /**
     * @param \Flarum\Extend\ExtenderInterface[] $extenders
     * @return SiteInterface
     */
    public function extendWith(array $extenders): SiteInterface
    {
        $this->extenders = $extenders;

        return $this;
    }
}
