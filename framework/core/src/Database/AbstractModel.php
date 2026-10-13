<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Database;

use Flarum\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Base model class, building on Eloquent.
 *
 * Adds the ability for custom relations to be added to a model during runtime.
 * These relations behave in the same way that you would expect; they can be
 * queried, eager loaded, and accessed as an attribute.
 */
abstract class AbstractModel extends Eloquent
{
    /**
     * Indicates if the model should be timestamped. Turn off by default.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * An array of callbacks to be run once after the model is saved.
     *
     * @var callable[]
     */
    protected array $afterSaveCallbacks = [];

    /**
     * An array of callbacks to be run once after the model is deleted.
     *
     * @var callable[]
     */
    protected array $afterDeleteCallbacks = [];

    /**
     * @internal
     */
    public static array $customCasts = [];

    /**
     * @internal
     */
    public static array $defaults = [];

    /**
     * Resolved casts, keyed by model class.
     *
     * Eloquent asks for the casts on every attribute access, so resolving the
     * class hierarchy each time is measurable: an index request resolved them
     * over 130,000 times. The result only changes when an extender registers
     * new casts, which is what flushCastsCache() is for.
     *
     * @var array<class-string, array<string, string>>
     */
    protected static array $castsCache = [];

    /**
     * An alias for the table name, used in queries.
     *
     * @internal
     */
    protected ?string $tableAlias = null;

    /**
     * If a model has unique keys, they should be defined here.
     *
     * @var array<string>|null
     */
    public ?array $uniqueKeys = null;

    /**
     * Whether a value can be one of this model's primary keys.
     *
     * For an integer key that means a whole number from 1 to 2147483647, the
     * range an auto-incrementing `integer` id column holds on every supported
     * database. PostgreSQL rejects any other value compared with such a column
     * (`abc`, `1.5`, `99999999999`) with an error instead of matching nothing,
     * as MySQL and SQLite do. Check first, and treat a value that fails as
     * "not found".
     */
    public static function isValidKey(mixed $key): bool
    {
        if ((new static)->getKeyType() !== 'int') {
            return true;
        }

        if (is_string($key) && ctype_digit($key)) {
            $key = (int) $key;
        }

        return is_int($key) && $key >= 1 && $key <= 2147483647;
    }

    public static function boot()
    {
        parent::boot();

        static::saved(function (self $model) {
            foreach ($model->releaseAfterSaveCallbacks() as $callback) {
                $callback($model);
            }
        });

        static::deleted(function (self $model) {
            foreach ($model->releaseAfterDeleteCallbacks() as $callback) {
                $callback($model);
            }
        });
    }

    public function __construct(array $attributes = [])
    {
        $this->attributes = [];

        foreach (array_merge(array_reverse(class_parents($this)), [static::class]) as $class) {
            $this->attributes = array_merge($this->attributes, Arr::get(static::$defaults, $class, []));
        }

        $this->attributes = array_map(function ($item) {
            return is_callable($item) ? $item($this) : $item;
        }, $this->attributes);

        parent::__construct($attributes);
    }

    public function getCasts(): array
    {
        if (isset(static::$castsCache[static::class])) {
            return static::$castsCache[static::class];
        }

        $casts = parent::getCasts();

        foreach (array_merge(array_reverse(class_parents($this)), [static::class]) as $class) {
            $casts = array_merge($casts, Arr::get(static::$customCasts, $class, []));
        }

        return static::$castsCache[static::class] = $casts;
    }

    /**
     * Discard the resolved casts, so that newly registered ones are picked up.
     *
     * @internal
     */
    public static function flushCastsCache(): void
    {
        static::$castsCache = [];
    }

    /**
     * Register a callback to be run once after the model is saved.
     */
    public function afterSave(callable $callback): void
    {
        $this->afterSaveCallbacks[] = $callback;
    }

    /**
     * Register a callback to be run once after the model is deleted.
     */
    public function afterDelete(callable $callback): void
    {
        $this->afterDeleteCallbacks[] = $callback;
    }

    /**
     * @return callable[]
     */
    public function releaseAfterSaveCallbacks(): array
    {
        $callbacks = $this->afterSaveCallbacks;

        $this->afterSaveCallbacks = [];

        return $callbacks;
    }

    /**
     * @return callable[]
     */
    public function releaseAfterDeleteCallbacks(): array
    {
        $callbacks = $this->afterDeleteCallbacks;

        $this->afterDeleteCallbacks = [];

        return $callbacks;
    }

    /**
     * @inheritDoc
     */
    public function newModelQuery()
    {
        /** @var \Illuminate\Database\Eloquent\Builder<static> $query */
        $query = parent::newModelQuery();

        if ($this->tableAlias) {
            $query->from($this->getTable().' as '.$this->tableAlias);
        }

        return $query;
    }

    public function qualifyColumn($column)
    {
        if (Str::contains($column, '.')) {
            return $column;
        }

        return ($this->tableAlias ?? $this->getTable()).'.'.$column;
    }

    public function withTableAlias(callable $callback): mixed
    {
        static $aliasCount = 0;
        $this->tableAlias = 'flarum_reserved_'.++$aliasCount;

        $result = $callback();

        $this->tableAlias = null;

        return $result;
    }

    // @phpstan-ignore-next-line
    public function newCollection(array $models = []): Collection
    {
        // @phpstan-ignore-next-line
        return new Collection($models);
    }

    public function __sleep()
    {
        // Closures cannot be serialized.
        // We should not need them if we are serializing a model.
        $this->afterSaveCallbacks = [];
        $this->afterDeleteCallbacks = [];

        return parent::__sleep();
    }
}
