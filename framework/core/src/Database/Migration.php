<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\Extend\Settings;
use Flarum\Settings\DatabaseSettingsRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/**
 * Migration factory.
 *
 * Implements some handy shortcuts for creating typical migrations.
 */
abstract class Migration
{
    public static function createTable(string $name, callable $definition): array
    {
        return [
            'up' => function (Builder $schema) use ($name, $definition) {
                $schema->create($name, function (Blueprint $table) use ($definition) {
                    $definition($table);
                });
            },
            'down' => function (Builder $schema) use ($name) {
                $schema->drop($name);
            }
        ];
    }

    public static function createTableIfNotExists(string $name, callable $definition): array
    {
        return [
            'up' => function (Builder $schema) use ($name, $definition) {
                if (! $schema->hasTable($name)) {
                    $schema->create($name, function (Blueprint $table) use ($definition) {
                        $definition($table);
                    });
                }
            },
            'down' => function (Builder $schema) use ($name) {
                $schema->dropIfExists($name);
            }
        ];
    }

    public static function renameTable(string $from, string $to): array
    {
        return [
            'up' => function (Builder $schema) use ($from, $to) {
                $schema->rename($from, $to);
            },
            'down' => function (Builder $schema) use ($from, $to) {
                $schema->rename($to, $from);
            }
        ];
    }

    public static function addColumns(string $tableName, array $columnDefinitions): array
    {
        return [
            'up' => function (Builder $schema) use ($tableName, $columnDefinitions) {
                $schema->table($tableName, function (Blueprint $table) use ($columnDefinitions) {
                    foreach ($columnDefinitions as $columnName => $options) {
                        $type = array_shift($options);
                        $table->addColumn($type, $columnName, $options);
                    }
                });
            },
            'down' => function (Builder $schema) use ($tableName, $columnDefinitions) {
                $schema->table($tableName, function (Blueprint $table) use ($columnDefinitions) {
                    $table->dropColumn(array_keys($columnDefinitions));
                });
            }
        ];
    }

    public static function dropColumns(string $tableName, array $columnDefinitions): array
    {
        $inverse = static::addColumns($tableName, $columnDefinitions);

        return [
            'up' => $inverse['down'],
            'down' => $inverse['up']
        ];
    }

    public static function renameColumn(string $tableName, string $from, string $to): array
    {
        return static::renameColumns($tableName, [$from => $to]);
    }

    public static function renameColumns(string $tableName, array $columnNames): array
    {
        return [
            'up' => function (Builder $schema) use ($tableName, $columnNames) {
                $schema->table($tableName, function (Blueprint $table) use ($columnNames) {
                    foreach ($columnNames as $from => $to) {
                        $table->renameColumn($from, $to);
                    }
                });
            },
            'down' => function (Builder $schema) use ($tableName, $columnNames) {
                $schema->table($tableName, function (Blueprint $table) use ($columnNames) {
                    foreach ($columnNames as $to => $from) {
                        $table->renameColumn($from, $to);
                    }
                });
            }
        ];
    }

    /**
     * Add default values for config values.
     *
     * @deprecated Use the Settings extender's `default` method instead to register settings.
     * @see Settings::default()
     */
    public static function addSettings(array $defaults): array
    {
        return [
            'up' => function (Builder $schema) use ($defaults) {
                $settings = new DatabaseSettingsRepository(
                    $schema->getConnection()
                );

                foreach ($defaults as $key => $value) {
                    $settings->set($key, $value);
                }
            },
            'down' => function (Builder $schema) use ($defaults) {
                $settings = new DatabaseSettingsRepository(
                    $schema->getConnection()
                );

                foreach (array_keys($defaults) as $key) {
                    $settings->delete($key);
                }
            }
        ];
    }

    public static function addPermissions(array $permissions): array
    {
        $rows = [];

        foreach ($permissions as $permission => $groups) {
            foreach ((array) $groups as $group) {
                $rows[] = [
                    'group_id' => $group,
                    'permission' => $permission,
                ];
            }
        }

        return [
            'up' => function (Builder $schema) use ($rows) {
                $db = $schema->getConnection();

                foreach ($rows as $row) {
                    if ($db->table('group_permission')->where($row)->exists()) {
                        continue;
                    }

                    if ($db->table('groups')->where('id', $row['group_id'])->doesntExist()) {
                        continue;
                    }

                    $db->table('group_permission')->insert($row);
                }
            },

            'down' => function (Builder $schema) use ($rows) {
                $db = $schema->getConnection();

                foreach ($rows as $row) {
                    $db->table('group_permission')->where($row)->delete();
                }
            }
        ];
    }
}
