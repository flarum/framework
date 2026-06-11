<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Audit;

use Carbon\Carbon;
use Flarum\User\User;

class AuditLogger
{
    /**
     * @var User|null
     */
    public static $actor = null;

    /**
     * @var string|null
     */
    public static $client = null;

    /**
     * @var string|null
     */
    public static $ipAddress = null;

    /**
     * Not stored, but used to know which request was used to trigger an event.
     *
     * @var string|null
     */
    public static $path = null;

    /**
     * Used internally to disable the logger after the database table has been intentionally destroyed.
     *
     * @var bool
     */
    public static $disabled = false;

    /**
     * List of known actions to expose for admin panel settings, grouped by the extension that declared them.
     *
     * @var string[][]
     */
    public static $registeredActions = [
        // This log action will always exist since it's part of the extension itself.
        // We manually register it here since it doesn't have its own extender.
        'flarum-audit' => [
            'audit_log_cleared',
        ],
    ];

    /**
     * Changes the behaviour during integration testing.
     *
     * @var bool
     */
    public static $testMode = false;

    protected static function getClient(): string
    {
        if (self::$client) {
            return self::$client;
        }

        if (php_sapi_name() == 'cli') {
            return 'cli';
        }

        return 'unknown';
    }

    public static function log(string $action, array $payload = []): void
    {
        if (self::$disabled) {
            return;
        }

        $actorId = self::$actor ? self::$actor->id : null;

        $log = new AuditLog();
        $log->actor_id = $actorId ?: null; // $actor->id might return 0 for guests which we turn into null
        $log->client = self::getClient();
        $log->ip_address = self::$ipAddress;
        $log->action = $action;
        $log->payload = count($payload) === 0 ? null : $payload;
        $log->created_at = Carbon::now();
        $log->save();
    }

    /**
     * Register action strings. These are used to list available actions in the admin settings.
     *
     * @param string|null $extension Extension that the actions belong to, for admin grouping. Null means core.
     * @param string ...$actions List of possible actions.
     */
    public static function register(?string $extension, string ...$actions): void
    {
        if (is_null($extension)) {
            $extension = 'core';
        }

        if (! array_key_exists($extension, self::$registeredActions)) {
            self::$registeredActions[$extension] = [];
        }

        foreach ($actions as $action) {
            if (! in_array($action, self::$registeredActions[$extension], true)) {
                self::$registeredActions[$extension][] = $action;
            }
        }
    }
}
