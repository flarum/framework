<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Branding;

use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;
use Flarum\Mail\EmailLogo;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;
use Intervention\Image\ImageManager;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The logos and icons a theme sets with the Theme extender.
 *
 * Each is drawn into the assets directory the way an upload would be, and
 * drawn again only when its file or options change. A request only compares
 * the files' sizes and modification times with what was drawn, once. What was
 * drawn is kept in a setting, so clearing the cache doesn't draw it again.
 */
class ThemeImages
{
    public const STATE_KEY = 'theme_images';

    /**
     * Raise to draw every theme image again, after changing how they are drawn.
     */
    protected const VERSION = 1;

    protected Cloud $assets;

    /**
     * What was drawn, checked once per request.
     *
     * @var array<string, array{signature: string, paths: array<string, string>, email_size?: string}>|null
     */
    protected ?array $state = null;

    public function __construct(
        protected Container $container,
        protected SettingsRepositoryInterface $settings,
        Factory $filesystemFactory
    ) {
        $this->assets = $filesystemFactory->disk('flarum-assets');
    }

    public function has(string $image): bool
    {
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

        $state = json_decode((string) $this->settings->get(self::STATE_KEY), true) ?: [];
        $config = $this->config();
        $changed = false;

        foreach (array_diff_key($state, $config) as $image => $entry) {
            $this->deletePaths($entry['paths']);
            unset($state[$image]);
            $changed = true;
        }

        foreach ($config as $image => $options) {
            $signature = $this->signature($image, $options);

            if (($state[$image]['signature'] ?? null) === $signature) {
                continue;
            }

            $this->deletePaths($state[$image]['paths'] ?? []);

            // An image that can't be drawn is left out until its file changes,
            // rather than tried again, and failing, on every request.
            try {
                $state[$image] = $this->draw($image, $options, $signature);
            } catch (Throwable $e) {
                $this->container->make(LoggerInterface::class)->error(sprintf(
                    'The %s set with the Theme extender, %s, could not be drawn: %s',
                    str_replace('_', ' ', $image),
                    $options['file'],
                    $e->getMessage()
                ));

                $state[$image] = ['signature' => $signature, 'paths' => []];
            }

            $changed = true;
        }

        if ($changed) {
            $this->settings->set(self::STATE_KEY, json_encode($state));
        }

        return $this->state = $state;
    }

    /**
     * @return array{signature: string, paths: array<string, string>, email_size?: string}
     */
    protected function draw(string $image, array $options, string $signature): array
    {
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
            array_map(fn (string $file) => [$file, @filesize($file), @filemtime($file)], $files),
            $image === 'app_icon' ? $this->background($options) : null,
            in_array($image, ['logo', 'logo_dark_mode'], true) ? $this->container->make('flarum.forum.logo') : null,
        ]));
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
     * @return array<string, array{file: string, svg?: string|null, background?: string|null, owner?: string|null}>
     */
    protected function config(): array
    {
        return $this->container->make('flarum.theme.images');
    }
}
