<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Deck;

use Flarum\Settings\SettingsRepositoryInterface;

/**
 * A member's deck is stored as a user preference: an ordered list of columns,
 * each naming a column type (which extensions may add to) and its parameters.
 * The client is the only writer, so everything here is treated as untrusted.
 */
class DeckLayout
{
    public const PREFERENCE_KEY = 'deckColumns';
    public const POLL_INTERVAL_SETTING = 'flarum-deck.poll_interval';
    public const MAX_COLUMNS_SETTING = 'flarum-deck.max_columns';

    public const WIDTHS = ['narrow', 'normal', 'wide'];

    /** Columns sit in one of up to this many rows. */
    public const ROWS = 2;

    /** Hard ceiling regardless of the admin setting; every column is a query. */
    public const COLUMN_LIMIT = 12;

    protected const MAX_PARAMS = 4;
    protected const MAX_PARAM_LENGTH = 200;

    /**
     * @return list<array{id: string, type: string, width: string, row: int, params: array<string, string|int>}>|null
     */
    public static function sanitize(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $max = static::maxColumns(resolve(SettingsRepositoryInterface::class));
        $columns = [];
        $ids = [];

        foreach (array_values($value) as $column) {
            if (count($columns) >= $max) {
                break;
            }

            $column = static::sanitizeColumn($column);

            if ($column === null || isset($ids[$column['id']])) {
                continue;
            }

            $ids[$column['id']] = true;
            $columns[] = $column;
        }

        return $columns;
    }

    /**
     * @return array{id: string, type: string, width: string, row: int, params: array<string, string|int>}|null
     */
    protected static function sanitizeColumn(mixed $column): ?array
    {
        if (! is_array($column)) {
            return null;
        }

        $id = $column['id'] ?? null;
        $type = $column['type'] ?? null;

        if (! is_string($id) || ! preg_match('/^[a-zA-Z0-9]{1,16}$/', $id)) {
            return null;
        }

        if (! is_string($type) || ! preg_match('/^[a-zA-Z][a-zA-Z0-9_.-]{0,63}$/', $type)) {
            return null;
        }

        $width = $column['width'] ?? 'normal';

        if (! in_array($width, static::WIDTHS, true)) {
            $width = 'normal';
        }

        $row = $column['row'] ?? 0;

        if (! is_int($row) || $row < 0 || $row >= static::ROWS) {
            $row = 0;
        }

        $params = [];

        foreach ((array) ($column['params'] ?? []) as $key => $param) {
            if (count($params) >= static::MAX_PARAMS) {
                break;
            }

            if (! is_string($key) || ! preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,31}$/', $key)) {
                continue;
            }

            if (is_int($param)) {
                $params[$key] = $param;
            } elseif (is_string($param) && mb_strlen($param) <= static::MAX_PARAM_LENGTH) {
                $params[$key] = $param;
            }
        }

        return compact('id', 'type', 'width', 'row', 'params');
    }

    public static function maxColumns(SettingsRepositoryInterface $settings): int
    {
        return max(1, min(static::COLUMN_LIMIT, (int) $settings->get(static::MAX_COLUMNS_SETTING)));
    }

    /**
     * Seconds between background refreshes when realtime is not connected;
     * 0 turns polling off.
     */
    public static function pollInterval(SettingsRepositoryInterface $settings): int
    {
        $interval = (int) $settings->get(static::POLL_INTERVAL_SETTING);

        return $interval <= 0 ? 0 : max(15, $interval);
    }
}
