<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Messages\DialogMessage\Filter;

use Flarum\Search\Database\DatabaseSearchState;
use Flarum\Search\Filter\FilterInterface;
use Flarum\Search\SearchState;
use Flarum\Search\ValidateFilterTrait;

/**
 * Messages by id, as a conversation already on screen asks for its messages
 * again after a reconnect.
 *
 * @implements FilterInterface<DatabaseSearchState>
 */
class IdFilter implements FilterInterface
{
    use ValidateFilterTrait;

    public function getFilterKey(): string
    {
        return 'id';
    }

    public function filter(SearchState $state, string|array $value, bool $negate): void
    {
        $state->getQuery()->whereIn('dialog_messages.id', $this->asIntArray($value), 'and', $negate);
    }
}
