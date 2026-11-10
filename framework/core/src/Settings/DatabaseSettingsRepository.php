<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Settings;

use Illuminate\Database\ConnectionInterface;

class DatabaseSettingsRepository implements SettingsRepositoryInterface
{
    public function __construct(
        protected ConnectionInterface $database
    ) {
    }

    public function all(): array
    {
        return $this->database->table('settings')->pluck('value', 'key')->all();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (is_null($value = $this->database->table('settings')->where('key', $key)->value('value'))) {
            return $default;
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        // A single statement, resolved against `key` — the table's primary key.
        // Reading first and then choosing insert or update lets two concurrent
        // writers both find the row missing and both insert it: one wins, the
        // other fails on the primary key. That is reachable whenever several
        // processes write the same setting for the first time at once, such as
        // on a fresh deploy or in the requests following a cache clear.
        $this->database->table('settings')->upsert(
            compact('key', 'value'),
            ['key'],
            ['value']
        );
    }

    public function delete(string $keyLike): void
    {
        $this->database->table('settings')->where('key', $keyLike)->delete();
    }
}
