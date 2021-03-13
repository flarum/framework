<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

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
    /**
     * Create a table.
     */
    public static function createTable($name, callable $definition)
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

    /**
     * Rename a table.
     */
    public static function renameTable($from, $to)
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

    /**
     * Add columns to a table.
     */
    public static function addColumns($tableName, array $columnDefinitions)
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

    /**
     * Drop columns from a table.
     */
    public static function dropColumns($tableName, array $columnDefinitions)
    {
        $inverse = static::addColumns($tableName, $columnDefinitions);

        return [
            'up' => $inverse['down'],
            'down' => $inverse['up']
        ];
    }

    /**
     * Rename a column.
     */
    public static function renameColumn($tableName, $from, $to)
    {
        return static::renameColumns($tableName, [$from => $to]);
    }

    /**
     * Rename multiple columns.
     */
    public static function renameColumns($tableName, array $columnNames)
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
     */
    public static function addSettings(array $defaults)
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

    /**
     * Add default permissions.
     */
    public static function addPermissions(array $permissions)
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
