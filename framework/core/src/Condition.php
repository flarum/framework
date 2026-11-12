<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum;

use Closure;
use Flarum\Database\MigrationContext;
use Flarum\Extension\ExtensionManager;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Schema\Builder;

class Condition
{
    protected function __construct(protected Closure $predicate)
    {
    }

    public static function extensionEnabled(string $extensionId): self
    {
        return self::extensionsEnabled([$extensionId]);
    }

    public static function extensionDisabled(string $extensionId): self
    {
        return self::extensionsDisabled([$extensionId]);
    }

    /** @param string[] $extensionIds */
    public static function extensionsEnabled(array $extensionIds): self
    {
        return new self(function (Container $container, ?MigrationContext $migration = null) use ($extensionIds): bool {
            if ($migration) {
                return array_diff($extensionIds, $migration->enabledExtensionIds) === [];
            }

            $extensions = $container->make(ExtensionManager::class);

            foreach ($extensionIds as $extensionId) {
                if (! $extensions->isEnabled($extensionId)) {
                    return false;
                }
            }

            return true;
        });
    }

    /** @param string[] $extensionIds */
    public static function extensionsDisabled(array $extensionIds): self
    {
        return new self(function (Container $container, ?MigrationContext $migration = null) use ($extensionIds): bool {
            if ($migration) {
                return array_intersect($extensionIds, $migration->enabledExtensionIds) === [];
            }

            $extensions = $container->make(ExtensionManager::class);

            foreach ($extensionIds as $extensionId) {
                if ($extensions->isEnabled($extensionId)) {
                    return false;
                }
            }

            return true;
        });
    }

    public static function setting(string $key, mixed $expected, bool $strict = false): self
    {
        return new self(function (SettingsRepositoryInterface $settings) use ($key, $expected, $strict): bool {
            $value = $settings->get($key);

            return $strict ? $value === $expected : $value == $expected;
        });
    }

    public static function all(callable|bool ...$conditions): self
    {
        return new self(function (Container $container, ?Builder $schema = null, ?MigrationContext $migration = null) use ($conditions): bool {
            foreach ($conditions as $condition) {
                if (! self::evaluate($condition, $container, $schema, $migration)) {
                    return false;
                }
            }

            return true;
        });
    }

    public static function any(callable|bool ...$conditions): self
    {
        return new self(function (Container $container, ?Builder $schema = null, ?MigrationContext $migration = null) use ($conditions): bool {
            foreach ($conditions as $condition) {
                if (self::evaluate($condition, $container, $schema, $migration)) {
                    return true;
                }
            }

            return false;
        });
    }

    public static function not(callable|bool $condition): self
    {
        return new self(fn (Container $container, ?Builder $schema = null, ?MigrationContext $migration = null): bool => ! self::evaluate($condition, $container, $schema, $migration));
    }

    protected static function evaluate(callable|bool $condition, Container $container, ?Builder $schema, ?MigrationContext $migration): bool
    {
        return is_bool($condition) ? $condition : (bool) $container->call($condition, self::parameters($container, $schema, $migration));
    }

    protected static function parameters(Container $container, ?Builder $schema, ?MigrationContext $migration): array
    {
        $schema ??= $migration?->schema;

        return [Container::class => $container]
            + ($schema ? ['schema' => $schema, Builder::class => $schema] : [])
            + ($migration ? [MigrationContext::class => $migration, SettingsRepositoryInterface::class => $migration->settings] : []);
    }

    public function __invoke(Container $container, ?Builder $schema = null, ?MigrationContext $migration = null): bool
    {
        return self::evaluate($this->predicate, $container, $schema, $migration);
    }
}
