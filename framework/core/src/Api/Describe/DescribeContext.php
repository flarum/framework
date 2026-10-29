<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Describe;

use Flarum\Api\Context;

/**
 * The synthetic context field, endpoint and sort closures are evaluated against
 * to build a resource descriptor. There is no real row and no query or body, so
 * row-dependent closures can check `$context instanceof DescribeContext` to
 * short-circuit; a closure that throws makes a field absent (visibility) or
 * `conditional` (writability).
 */
class DescribeContext extends Context
{
    /**
     * @var 'create'|'update'|'show'|'index'|null
     */
    protected ?string $mode = null;

    /**
     * @return 'create'|'update'|'show'|'index'|null
     */
    public function mode(): ?string
    {
        return $this->mode;
    }

    /**
     * @param 'create'|'update'|'show'|'index' $mode
     */
    public function withMode(string $mode): static
    {
        $new = clone $this;
        $new->mode = $mode;

        return $new;
    }
}
