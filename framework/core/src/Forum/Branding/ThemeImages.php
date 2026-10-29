<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Branding;

use Carbon\Carbon;
use Closure;
use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;
use Flarum\Mail\EmailLogo;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;
use Intervention\Image\ImageManager;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The logos and icons a theme sets with the Theme extender.
 *
 * Each is drawn into the assets directory the way an upload would be, and
 * drawn again only when its file's contents or its options change. A file is
 * only read again when its size or modification time changes. What was drawn
 * is kept in a setting, so clearing the cache doesn't draw it again.
 *
 * While a theme sets an image, the setting an upload of it is kept in holds
 * the drawn file instead, when that is the kind of file an upload would be.
 * The upload is put back when the theme stops setting the image.
 *
 * Files an image no longer uses are kept for 30 days, for pages and emails
 * that still show them, and taken back if the image returns to them.
 */
class ThemeImages
{
    public const STATE_KEY = 'theme_images';

    /**
     * Raise to draw every theme image again, after changing how they are drawn.
     */
    protected const VERSION = 1;

    /**
     * How long, in seconds, the files an image no longer uses are kept.
     */
    protected const KEEP_RETIRED_FOR = 30 * 24 * 60 * 60;

    /**
     * image => [setting => the drawn file it holds].
     */
    protected const SETTINGS = [
        'logo' => ['logo_path' => 'image'],
        'logo_dark_mode' => ['logo_dark_mode_path' => 'image'],
        'favicon' => ['favicon_path' => 'png'],
        'email_logo' => [EmailLogo::PATH_KEY => 'email', EmailLogo::SIZE_KEY => 'email_size'],
    ];

    protected Cloud $assets;

    /**
     * What was drawn, checked once per request.
     *
     * @var array<string, array{signature: string, paths: array<string, string>, email_size?: string, replaced?: array<string, mixed>}>|null
     */
    protected ?array $state = null;

    /**
     * @var array<string, array{file: string, svg?: string|null, background?: string|null, owner?: string|null}>|null
     */
    protected ?array $config = null;

    public function __construct(
        protected Container $container,
        protected SettingsRepositoryInterface $settings,
        Factory $filesystemFactory
    ) {
        $this->assets = $filesystemFactory->disk('flarum-assets');
    }

    public function has(string $image): bool
    {
        $this->sync();

        return isset($this->config()[$image]);
    }

    /**
     * @return array<string, string|null> image => title of the extension that set it
     */
    public function owners(): array
    {
        return array_map(fn (array $options) => $options['owner'] ?? null, $this->config());
    }

    /**
     * @return array<string, string> name => URL
     */
    public function urls(string $image): array
    {
        return array_map(fn (string $path) => $this->assets->url($path), $this->sync()[$image]['paths'] ?? []);
    }

    /**
     * @return array<string, string> name => path on the assets disk
     */
    public function paths(string $image): array
    {
        return $this->sync()[$image]['paths'] ?? [];
    }

    /**
     * The email copy of a theme image, as EmailLogo resolves it.
     *
     * @return array{url: string, width: int|null, height: int|null}|null
     */
    public function email(string $image): ?array
    {
        $entry = $this->sync()[$image] ?? null;

        if (! isset($entry['paths']['email'])) {
            return null;
        }

        return ['url' => $this->assets->url($entry['paths']['email'])] + EmailLogo::displaySizeOf($entry['email_size'] ?? null);
    }

    public function assertNotSet(string $image, string $field): void
    {
        if (! $this->has($image)) {
            return;
        }

        $owner = $this->owners()[$image];

        throw new ValidationException([
            $field => $this->container->make(TranslatorInterface::class)->trans(
                $owner ? 'core.api.image_set_by_theme_message' : 'core.api.image_set_by_code_message',
                ['extension' => $owner]
            ),
        ]);
    }

    /**
     * @return array<string, array{signature: string, paths: array<string, string>, email_size?: string}>
     */
    protected function sync(): array
    {
        if ($this->state !== null) {
            return $this->state;
        }

        $stored = json_decode((string) $this->settings->get(self::STATE_KEY), true) ?: [];
        $state = $stored['images'] ?? [];
        $retired = $stored['retired'] ?? [];
        $config = $this->config();
        $now = Carbon::now()->getTimestamp();
        $changed = false;

        foreach (array_diff_key($state, $config) as $image => $entry) {
            $retired[] = $this->retire($image, $entry, $now);
            $this->mirror($image, ['paths' => [], 'replaced' => $entry['replaced'] ?? []]);
            unset($state[$image]);
            $changed = true;
        }

        foreach ($config as $image => $options) {
            $signature = $this->signature($image, $options);

            if (($state[$image]['signature'] ?? null) === $signature) {
                continue;
            }

            $replaced = $state[$image]['replaced'] ?? $this->replaced($image);

            if (isset($state[$image])) {
                $retired[] = $this->retire($image, $state[$image], $now);
            }

            $state[$image] = $this->reuse($retired, $image, $signature) ?? $this->drawOrLeaveOut($image, $options, $signature);
            $state[$image]['replaced'] = $replaced;
            $this->mirror($image, $state[$image]);

            $changed = true;
        }

        foreach ($retired as $i => $entry) {
            if ($entry['retired_at'] <= $now - self::KEEP_RETIRED_FOR) {
                $this->deletePaths($entry['paths']);
                unset($retired[$i]);
                $changed = true;
            }
        }

        if ($changed) {
            $this->settings->set(self::STATE_KEY, json_encode(['images' => $state, 'retired' => array_values($retired)]));
        }

        return $this->state = $state;
    }

    /**
     * An image that can't be drawn is left out until its file changes, rather
     * than tried again, and failing, on every request.
     *
     * @return array{signature: string, paths: array<string, string>, email_size?: string}
     */
    protected function drawOrLeaveOut(string $image, array $options, string $signature): array
    {
        try {
            return $this->draw($image, $options, $signature);
        } catch (Throwable $e) {
            $this->container->make(LoggerInterface::class)->error(sprintf(
                'The %s set with the Theme extender, %s, could not be drawn: %s',
                str_replace('_', ' ', $image),
                $options['file'],
                $e->getMessage()
            ));

            return ['signature' => $signature, 'paths' => []];
        }
    }

    /**
     * @return array{image: string, signature: string, paths: array<string, string>, email_size?: string, retired_at: int}
     */
    protected function retire(string $image, array $entry, int $now): array
    {
        return ['image' => $image, 'retired_at' => $now] + array_diff_key($entry, ['replaced' => null]);
    }

    /**
     * Takes back the files drawn for an image before, if they are still kept.
     *
     * @return array{signature: string, paths: array<string, string>, email_size?: string}|null
     */
    protected function reuse(array &$retired, string $image, string $signature): ?array
    {
        foreach ($retired as $i => $entry) {
            if ($entry['image'] === $image && $entry['signature'] === $signature) {
                unset($retired[$i]);

                return array_diff_key($entry, ['image' => null, 'retired_at' => null]);
            }
        }

        return null;
    }

    /**
     * @return array{signature: string, paths: array<string, string>, email_size?: string}
     */
    protected function draw(string $image, array $options, string $signature): array
    {
        $this->assertDrawable($image, $options);

        $data = (string) file_get_contents($options['file']);
        $files = [];
        $emailSize = null;

        switch ($image) {
            case 'logo':
            case 'logo_dark_mode':
                $files['image'] = $this->isSvg($options['file']) ? [$data, 'svg'] : $this->logo($data);

                if ($image === 'logo' && ! $this->isSvg($options['file'])) {
                    [$files['email'], $emailSize] = $this->encodeEmail($data);
                }

                break;

            case 'email_logo':
                [$files['email'], $emailSize] = $this->encodeEmail($data);

                break;

            case 'favicon':
                $favicon = $this->container->make(IconSet::class)->favicon($data);
                $files['png'] = [$favicon['png'], 'png'];
                $files['ico'] = [$favicon['ico'], 'ico'];

                if ($options['svg'] ?? null) {
                    $files['svg'] = [(string) file_get_contents($options['svg']), 'svg'];
                }

                break;

            case 'app_icon':
                foreach ($this->container->make(IconSet::class)->appIcon($data, $this->background($options)) as $name => $contents) {
                    $files[$name] = [$contents, pathinfo($name, PATHINFO_EXTENSION)];
                }

                break;
        }

        $prefix = 'theme-'.str_replace('_', '-', $image);
        $short = substr($signature, 0, 8);
        $paths = [];

        foreach ($files as $name => [$contents, $extension]) {
            $paths[$name] = $prefix.'-'.pathinfo($name, PATHINFO_FILENAME)."-$short.$extension";
            $this->assets->put($paths[$name], $contents, StoredFiles::options($paths[$name]));
        }

        return array_filter(['signature' => $signature, 'paths' => $paths, 'email_size' => $emailSize]);
    }

    protected function assertDrawable(string $image, array $options): void
    {
        foreach (array_filter([$options['file'], $options['svg'] ?? null]) as $file) {
            if (! is_file($file)) {
                throw new InvalidArgumentException($file === $options['file'] ? 'It does not exist.' : "Its SVG version, $file, does not exist.");
            }
        }

        if ($this->isSvg($options['file']) && ! in_array($image, ['logo', 'logo_dark_mode'], true)) {
            throw new InvalidArgumentException('It must be a PNG, JPEG, WebP or GIF file, not an SVG.');
        }

        if (isset($options['svg']) && ! $this->isSvg($options['svg'])) {
            throw new InvalidArgumentException("Its SVG version, {$options['svg']}, must be an SVG file.");
        }
    }

    /**
     * @return array{0: string, 1: string} contents and extension
     */
    protected function logo(string $data): array
    {
        $size = $this->container->make('flarum.forum.logo');

        $image = $this->container->make(ImageManager::class)->read($data)->scale(height: $size['height']);

        return $image->isAnimated() ? [(string) $image->toGif(), 'gif'] : [(string) $image->toWebp(quality: $size['quality']), 'webp'];
    }

    /**
     * @return array{0: array{0: string, 1: string}, 1: string} the file, and its size as "WIDTHxHEIGHT"
     */
    protected function encodeEmail(string $data): array
    {
        [$encoded, $extension, $size] = $this->container->make(EmailLogo::class)->encode(
            $this->container->make(ImageManager::class)->read($data)
        );

        return [[(string) $encoded, $extension], $size];
    }

    protected function background(array $options): string
    {
        return $options['background'] ?? $this->container->make(AppIcon::class)->background();
    }

    protected function signature(string $image, array $options): string
    {
        $files = array_filter([$options['file'], $options['svg'] ?? null]);

        return md5(json_encode([
            self::VERSION,
            $image,
            array_map(fn (string $file) => $this->contents($file), $files),
            $image === 'app_icon' ? $this->background($options) : null,
            in_array($image, ['logo', 'logo_dark_mode'], true) ? $this->container->make('flarum.forum.logo') : null,
        ]));
    }

    /**
     * @return array<string, mixed> setting => its value before the theme set the image
     */
    protected function replaced(string $image): array
    {
        $values = [];

        foreach (array_keys(self::SETTINGS[$image] ?? []) as $key) {
            $values[$key] = $this->settings->get($key);
        }

        return $values;
    }

    /**
     * Puts the drawn file in the image's settings, or the values they had
     * before when it isn't the kind of file an upload would be.
     */
    protected function mirror(string $image, array $entry): void
    {
        $settings = self::SETTINGS[$image] ?? [];
        $file = $entry['paths'][reset($settings)] ?? null;
        $drawn = $file !== null && ! $this->isSvg($file);

        foreach ($settings as $key => $name) {
            if ($drawn) {
                $value = $name === 'email_size' ? ($entry['email_size'] ?? null) : $entry['paths'][$name];
            } else {
                $value = $entry['replaced'][$key] ?? null;
            }

            if ($this->settings->get($key) !== $value) {
                $this->settings->set($key, $value);
            }
        }
    }

    /**
     * A hash of the file's contents, read again only when its size or
     * modification time changes.
     */
    protected function contents(string $file): ?string
    {
        if (! is_file($file)) {
            return null;
        }

        return $this->container->make(Repository::class)->rememberForever(
            'theme-image.'.md5($file.'|'.filesize($file).'|'.filemtime($file)),
            fn () => md5_file($file)
        );
    }

    /**
     * @param array<string, string> $paths
     */
    protected function deletePaths(array $paths): void
    {
        foreach ($paths as $path) {
            if ($this->assets->exists($path)) {
                $this->assets->delete($path);
            }
        }
    }

    protected function isSvg(string $file): bool
    {
        return strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'svg';
    }

    /**
     * The images set on this request, with their callables called.
     *
     * @return array<string, array{file: string, svg?: string|null, background?: string|null, owner?: string|null}>
     */
    protected function config(): array
    {
        if ($this->config !== null) {
            return $this->config;
        }

        $config = [];

        foreach ($this->container->make('flarum.theme.images') as $image => $options) {
            $options = array_map(fn ($value) => $value instanceof Closure ? $this->container->call($value) : $value, $options);

            if ($options['file'] !== null) {
                $config[$image] = $options;
            }
        }

        return $this->config = $config;
    }
}
