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
 * A synthetic context used to build a resource descriptor.
 *
 * Field, endpoint and sort closures are evaluated against it to infer what the
 * actor may see and write. Its model is an unsaved instance (marked as existing
 * for `update` and `show`), and its request is a stand-in derived from the
 * describe request: it carries the actor, but its query parameters, parsed body
 * and route parameters (`routeParameters` attribute) are stripped. Row-dependent closures can check `$context instanceof
 * DescribeContext` to short-circuit; a closure that throws makes a field absent
 * (visibility) or `conditional` (writability).
 *
 * The `with*()` methods clone, so derived contexts stay a `DescribeContext`.
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
