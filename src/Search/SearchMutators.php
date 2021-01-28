<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

/**
 * Wrapper for an array of search mutator callbacks so we
 * can pass it to searchers via dependency injection.
 */
class SearchMutators
{
    protected $mutators;

    public function __construct(array $mutators = [])
    {
        $this->mutators = $mutators;
    }

    public function getMutators(): array
    {
        return $this->mutators;
    }
}
