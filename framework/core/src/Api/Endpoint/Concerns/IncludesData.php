<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Endpoint\Concerns;

use Tobyz\JsonApiServer\Context;

trait IncludesData
{
    use \Tobyz\JsonApiServer\Endpoint\Concerns\IncludesData {
        getInclude as private getRequestedInclude;
    }

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

    /**
     * Naming any includes turns off the endpoint's default ones, extensions'
     * among them. A client that sends `withDefaultIncludes` as well gets the
     * defaults on top of the includes it names.
     */
    private function getInclude(Context $context): array
    {
        $query = $context->request->getQueryParams();

        if (isset($query['include']) && filter_var($query['withDefaultIncludes'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $include = is_array($query['include']) ? $query['include'] : explode(',', $query['include']);

            $query['include'] = implode(',', array_unique(array_filter([...$this->defaultInclude ?? [], ...$include])));

            $context = $context->withRequest($context->request->withQueryParams($query));
        }

        return $this->getRequestedInclude($context);
    }
}
