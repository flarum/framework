<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Branding;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\Str;

/**
 * The files derived from an uploaded image, each named in a setting.
 */
class StoredFiles
{
    protected const CONTENT_TYPES = [
        'gif' => 'image/gif',
        'ico' => 'image/x-icon',
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
    ];
    protected Cloud $assets;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        Factory $filesystemFactory
    ) {
        $this->assets = $filesystemFactory->disk('flarum-assets');
    }

    /**
     * Store a file under a fresh name, replacing the one the setting names.
     */
    public function replace(string $settingKey, ?string $contents, string $prefix, string $extension): void
    {
        $this->delete($settingKey);

        if ($contents === null) {
            return;
        }

        $path = $prefix.'-'.Str::lower(Str::random(8)).'.'.$extension;

        $this->assets->put($path, $contents, self::options($path));
        $this->settings->set($settingKey, $path);
    }

    /**
     * Store a set of files, replacing the set the setting names.
     *
     * @param array<string, string> $files file name, with its extension => contents
     */
    public function replaceSet(string $settingKey, array $files): void
    {
        $this->deleteSet($settingKey);

        $suffix = Str::lower(Str::random(8));
        $paths = [];

        foreach ($files as $name => $contents) {
            $paths[$name] = pathinfo($name, PATHINFO_FILENAME)."-$suffix.".pathinfo($name, PATHINFO_EXTENSION);
            $this->assets->put($paths[$name], $contents, self::options($paths[$name]));
        }

        $this->settings->set($settingKey, json_encode($paths));
    }

    /**
     * Options for storing an image under a name no other contents will have.
     *
     * The content type is given rather than left for a remote disk, such as S3,
     * to guess from the bytes, which can take an SVG for text. As the name changes
     * whenever the image does, it can be cached for good.
     */
    public static function options(string $path): array
    {
        $type = self::CONTENT_TYPES[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;

        return array_filter([
            'mimetype' => $type,
            'CacheControl' => 'public, max-age=31536000, immutable',
        ]);
    }

    public function delete(string $settingKey): void
    {
        $path = $this->settings->get($settingKey);

        if ($path && $this->assets->exists($path)) {
            $this->assets->delete($path);
        }

        $this->settings->set($settingKey, null);
    }

    public function deleteSet(string $settingKey): void
    {
        foreach ($this->paths($settingKey) as $path) {
            if ($this->assets->exists($path)) {
                $this->assets->delete($path);
            }
        }

        $this->settings->set($settingKey, null);
    }

    /**
     * @return array<string, string> name => path
     */
    public function paths(string $settingKey): array
    {
        return json_decode((string) $this->settings->get($settingKey), true) ?: [];
    }
}
