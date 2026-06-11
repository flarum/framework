<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit\Search;

/**
 * Registry of the search filters (gambits) available in the audit log browser.
 *
 * This is exposed to the frontend so the browser can show clickable usage examples and a
 * help panel, making the otherwise-undiscoverable gambit syntax (e.g. `actor:bob`)
 * self-documenting. Third-party extensions that add their own gambits can register matching
 * help here.
 */
class AuditGambits
{
    /**
     * @var array<array{key: string, example: string, description: string|null, values: string[], extension: string|null}>
     */
    public static $filters = [];

    /**
     * Register a filter for display in the audit browser's search help.
     *
     * @param string $key The gambit key, e.g. "actor".
     * @param string $example A complete example query, e.g. "actor:bob".
     * @param string|null $description A translation key describing what the filter matches.
     * @param string[] $values Known accepted values (e.g. for `client`), exposed as ready-to-use chips.
     * @param string|null $extension Extension that provides the gambit, for grouping. Null means core/audit.
     */
    public static function register(string $key, string $example, ?string $description = null, array $values = [], ?string $extension = null): void
    {
        foreach (self::$filters as $filter) {
            if ($filter['key'] === $key) {
                return;
            }
        }

        self::$filters[] = [
            'key' => $key,
            'example' => $example,
            'description' => $description,
            'values' => $values,
            'extension' => $extension,
        ];
    }
}
