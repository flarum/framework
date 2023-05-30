<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Search;

abstract class AbstractRegexGambit implements GambitInterface
{
    /**
     * The regex pattern to match the bit against.
     */
    abstract protected function getGambitPattern(): string;

    public function apply(SearchState $search, string $bit): bool
    {
        if ($matches = $this->match($bit)) {
            list($negate) = array_splice($matches, 1, 1);

            $this->conditions($search, $matches, (bool) $negate);
        }

        return (bool) $matches;
    }

    /**
     * Match the bit against this gambit.
     */
    protected function match(string $bit): ?array
    {
        if (! empty($bit) && preg_match('/^(-?)'.$this->getGambitPattern().'$/i', $bit, $matches)) {
            return $matches;
        }

        return null;
    }

    /**
     * Apply conditions to the search, given that the gambit was matched.
     *
     * @param array $matches An array of matches from the search bit.
     * @param bool $negate Whether the bit was negated, and thus whether the conditions should be negated.
     */
    abstract protected function conditions(SearchState $search, array $matches, bool $negate): void;
}
