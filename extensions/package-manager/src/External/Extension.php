<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\External;

/**
 * @property string $name
 * @property string $title
 * @property string $description
 * @property string $icon_url
 * @property array $icon
 * @property string $license
 * @property string $highest_version
 * @property string $http_uri
 * @property string $discuss_uri
 * @property string $vendor
 * @property bool $is_premium
 * @property bool $is_locale
 * @property string $locale
 * @property string $latest_flarum_version_supported
 * @property bool $compatible_with_latest_flarum
 * @property bool $listed_privately
 * @property int $downloads
 */
class Extension
{
    protected array $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
    }

    protected function casts(): array
    {
        return [
            'is_premium' => 'bool',
            'is_locale' => 'bool',
            'compatible_with_latest_flarum' => 'bool',
            'listed_privately' => 'bool',
            'downloads' => 'int',
        ];
    }

    public function extensionId(): string
    {
        return \Flarum\Extension\Extension::nameToId($this->name);
    }

    public function getAttribute(string $key): mixed
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->castAttribute($key, $this->attributes[$key]);
        }

        return null;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    protected function castAttribute(string $key, mixed $value): mixed
    {
        if (array_key_exists($key, $this->casts())) {
            $cast = $this->casts()[$key];

            if (is_string($cast) && function_exists($func = $cast.'val')) {
                return $func($value);
            }
        }

        return $value;
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function __unset(string $key): void
    {
        unset($this->attributes[$key]);
    }
}
