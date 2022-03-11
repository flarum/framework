<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Properties;

/**
 * @see https://github.com/psalm/laravel-psalm-plugin/blob/master/src/SchemaTable.php
 */
final class SchemaTable
{
    /** @var string */
    public $name;

    /** @var SchemaColumn[] */
    public $columns = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function setColumn(SchemaColumn $column): void
    {
        $this->columns[$column->name] = $column;
    }

    public function renameColumn(string $oldName, string $newName): void
    {
        if (! isset($this->columns[$oldName])) {
            return;
        }

        $oldColumn = $this->columns[$oldName];

        unset($this->columns[$oldName]);

        $oldColumn->name = $newName;

        $this->columns[$newName] = $oldColumn;
    }

    public function dropColumn(string $columnName): void
    {
        unset($this->columns[$columnName]);
    }
}
