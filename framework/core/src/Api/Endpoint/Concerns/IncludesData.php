<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Endpoint\Concerns;

trait IncludesData
{
    use \Tobyz\JsonApiServer\Endpoint\Concerns\IncludesData;

    public function addDefaultInclude(array $include): static
    {
        $this->defaultInclude = array_merge($this->defaultInclude ?? [], $include);

        return $this;
    }

    public function removeDefaultInclude(array $include): static
    {
        $this->defaultInclude = array_diff($this->defaultInclude ?? [], $include);

        return $this;
    }
}
