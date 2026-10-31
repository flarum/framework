<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Ai;

use Carbon\Carbon;
use Illuminate\Database\ConnectionInterface;

/**
 * The API keys of the connections set up on the admin page, in their own table
 * so they're never among the settings sent to the admin page.
 *
 * Changes from a settings save are staged while the save is checked and only
 * written once every setting in it has been, so a save another extension
 * rejects leaves the keys as they were.
 *
 * @internal
 */
class ConnectionKeys
{
    /**
     * @var array<string, string|null> Connection name to its new key, or null to remove it.
     */
    protected array $staged = [];

    /**
     * @var array<string, string>|null
     */
    protected ?array $keys = null;

    public function __construct(
        protected ConnectionInterface $db
    ) {
    }

    public function get(string $connection): ?string
    {
        return $this->all()[$connection] ?? null;
    }

    /**
     * @return string[] The connections that have a key.
     */
    public function names(): array
    {
        return array_keys($this->all());
    }

    /**
     * @param array<string, string|null> $changes
     */
    public function stage(array $changes): void
    {
        $this->staged = $changes;
    }

    public function commit(): void
    {
        foreach ($this->staged as $connection => $key) {
            if ($key === null) {
                $this->db->table('ai_connection_keys')->where('connection', $connection)->delete();
            } else {
                $this->db->table('ai_connection_keys')->updateOrInsert(
                    ['connection' => $connection],
                    ['key' => $key, 'updated_at' => Carbon::now()]
                );
            }
        }

        $this->staged = [];
        $this->keys = null;
    }

    /**
     * @return array<string, string>
     */
    protected function all(): array
    {
        return $this->keys ??= $this->db->table('ai_connection_keys')->pluck('key', 'connection')->all();
    }
}
