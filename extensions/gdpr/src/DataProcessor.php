<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr;

use Flarum\User\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Class DataProcessor.
 *
 * Handles the data types and user column removal for the GDPR extension.
 */
final class DataProcessor
{
    /**
     * @var array<string, null|string> Associative array with data type class as key and extension ID as value.
     */
    private static $types = [
        Data\Forum::class => null,
        Data\Assets::class => null,
        Data\Posts::class => null,
        Data\Tokens::class => null,
        Data\Discussions::class => null,
        Data\User::class => null, // Ought to be last at all times.
    ];

    /**
     * @var array<string, string|null> Map of column name => extension ID (or null for core).
     */
    private static array $removeUserColumns = [];

    /**
     * @var ColumnAction[]
     */
    private static $columnActions = [];

    /**
     * Additional PII keys for serialization anonymization, beyond those declared by registered
     * data types via {@see DataType::piiFields()}. Use this only for PII fields that don't
     * belong to any registered data type.
     *
     * @var array<string, string|null> Map of key name => extension ID (or null for core).
     */
    private static array $extraPiiKeysForSerialization = [];

    /**
     * Add a data type to the list.
     *
     * @param string      $class       The class name of the data type.
     * @param string|null $extensionId The ID of the extension adding the data type.
     */
    public static function addType(string $class, ?string $extensionId = null): void
    {
        // Separate the User entry and the rest of the array
        $withoutUser = Arr::except(self::$types, [Data\User::class]);
        $userEntry = [Data\User::class => self::$types[Data\User::class]];

        // Add the new class to the array without the User entry, then append the User entry back
        self::$types = $withoutUser + [$class => $extensionId] + $userEntry;
    }

    /**
     * Remove a data type from the list.
     *
     * @param string $class The class name of the data type to remove.
     */
    public static function removeType(string $class): void
    {
        unset(self::$types[$class]);
    }

    /**
     * Set the entire list of data types.
     *
     * @param array<string, null|string> $types Associative array with data type class as key and extension ID as value.
     */
    public static function setTypes(array $types): void
    {
        self::$types = $types;
    }

    /**
     * Add columns to the list of user columns to be removed.
     *
     * @param string[]    $columns     List of column names.
     * @param string|null $extensionId The ID of the extension registering the columns.
     */
    public static function removeUserColumns(array $columns, ?string $extensionId = null): void
    {
        foreach ($columns as $column) {
            self::$removeUserColumns[$column] = $extensionId;
        }
    }

    /**
     * Reset the removable user columns list. Intended for use in tests.
     */
    public static function resetRemovableUserColumns(): void
    {
        self::$removeUserColumns = [];
    }

    /**
     * Retrieve the list of data types.
     *
     * @return array<string, null|string> Associative array with data type class as key and extension ID as value.
     */
    public function types(): array
    {
        return self::$types;
    }

    /**
     * Retrieve the list of user columns to be removed (column names only).
     *
     * @return string[] List of column names.
     */
    public function removableUserColumns(): array
    {
        return array_keys(self::$removeUserColumns);
    }

    /**
     * Retrieve the full map of removable user columns with their registering extension IDs.
     *
     * @return array<string, string|null> Map of column name => extension ID (or null for core).
     */
    public function removableUserColumnsWithExtensions(): array
    {
        return self::$removeUserColumns;
    }

    public function allUserColumns(): array
    {
        $user = new User();
        $connection = $user->getConnection();
        $table = $user->getTable();

        // Get column listings for the user table
        $columns = $connection->getSchemaBuilder()->getColumns($table);
        $columnDetails = [];

        foreach ($columns as $column) {
            $columnDetails[$column['name']] = [
                'type' => $column['type_name'],
                'length' => str_contains($column['type'], '(')
                    ? intval(Str::of($column['type'])->afterLast('(')->beforeLast(')')->toString())
                    : null,
                'default' => $column['default'],
                'nullable' => $column['nullable'],
            ];
        }

        return $columnDetails;
    }

    public static function addColumnAction(ColumnAction $action): void
    {
        self::$columnActions[$action->getColumn()] = $action;
    }

    /**
     * @return ColumnAction[]
     */
    public function getColumnActions(): array
    {
        return self::$columnActions;
    }

    /**
     * Reset the extra PII keys list. Intended for use in tests.
     */
    public static function resetExtraPiiKeysForSerialization(): void
    {
        self::$extraPiiKeysForSerialization = [];
    }

    /**
     * Register additional PII keys for serialization anonymization that are not declared
     * by any registered data type. Prefer declaring PII fields on the data type itself
     * via {@see DataType::piiFields()} wherever possible.
     *
     * @param string[]    $keys
     * @param string|null $extensionId The ID of the extension registering the keys.
     */
    public static function addPiiKeysForSerialization(array $keys, ?string $extensionId = null): void
    {
        foreach ($keys as $key) {
            if (! array_key_exists($key, self::$extraPiiKeysForSerialization)) {
                self::$extraPiiKeysForSerialization[$key] = $extensionId;
            }
        }
    }

    /**
     * Get a map of every PII key to the extension ID that declared it.
     * Keys declared by built-in types or core are mapped to null.
     * When two sources declare the same key, the first wins (type-declared > extra).
     *
     * @return array<string, string|null> Map of key name => extension ID (or null for core).
     */
    public function getPiiKeysWithExtensions(): array
    {
        $result = [];

        foreach (self::$types as $typeClass => $extensionId) {
            foreach ($typeClass::piiFields() as $field) {
                if (! array_key_exists($field, $result)) {
                    $result[$field] = $extensionId;
                }
            }
        }

        foreach (self::$extraPiiKeysForSerialization as $field => $extensionId) {
            if (! array_key_exists($field, $result)) {
                $result[$field] = $extensionId;
            }
        }

        return $result;
    }

    /**
     * Get the full list of PII keys for serialization anonymization.
     * Aggregates fields declared by all registered data types, plus any extras
     * added via {@see addPiiKeysForSerialization()}.
     *
     * @return string[]
     */
    public function getPiiKeysForSerialization(): array
    {
        /** @var string[] $fromTypes */
        $fromTypes = array_merge(
            ...array_map(fn (string $type) => $type::piiFields(), array_keys(self::$types))
        );

        /** @var string[] $merged */
        $merged = array_unique(array_merge($fromTypes, array_keys(self::$extraPiiKeysForSerialization)));

        return array_values($merged);
    }
}
