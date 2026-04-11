<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\Str;
use Intervention\Image\Interfaces\ImageInterface;

class AvatarUploader
{
    protected Cloud $uploadDir;

    /** Sizes to generate: base (1×), @2x, @3x. */
    protected const SIZES = [
        '' => 100,
        '@2x' => 200,
        '@3x' => 300,
    ];

    public function __construct(Factory $filesystemFactory)
    {
        $this->uploadDir = $filesystemFactory->disk('flarum-avatars');
    }

    /**
     * Upload pre-sized avatar images provided by an OAuth driver.
     *
     * The caller is responsible for providing correctly sized images.
     * Only the variants whose images are non-null are stored.
     *
     * @param ImageInterface      $image1x Base (1×) image
     * @param ImageInterface|null $image2x 2× image (200px), or null to skip
     * @param ImageInterface|null $image3x 3× image (300px), or null to skip
     */
    public function uploadPresized(User $user, ImageInterface $image1x, ?ImageInterface $image2x, ?ImageInterface $image3x): void
    {
        $avatarBase = Str::random();
        $extension = $image1x->isAnimated() ? 'gif' : 'webp';
        $basePath = $avatarBase.'.'.$extension;

        $this->removeFileAfterSave($user);
        $user->changeAvatarPath($basePath);

        $variants = ['' => $image1x, '@2x' => $image2x, '@3x' => $image3x];

        foreach ($variants as $suffix => $image) {
            if ($image === null) {
                continue;
            }

            $encoded = $image->isAnimated() ? $image->toGif() : $image->toWebp();
            $path = $this->variantPath($basePath, $suffix);

            $this->uploadDir->put($path, $encoded);
        }
    }

    public function upload(User $user, ImageInterface $image): void
    {
        $avatarBase = Str::random();
        $isAnimated = $image->isAnimated();
        $extension = $isAnimated ? 'gif' : 'webp';
        $basePath = $avatarBase.'.'.$extension;

        // Read source dimensions before any cover() call, since cover() mutates the image in place.
        $sourceWidth = $image->width();
        $sourceHeight = $image->height();

        $this->removeFileAfterSave($user);
        $user->changeAvatarPath($basePath);

        foreach (self::SIZES as $suffix => $size) {
            // Never upscale — skip this variant if the source is too small.
            if ($sourceWidth < $size || $sourceHeight < $size) {
                continue;
            }

            $resized = clone $image;
            $resized->cover($size, $size);
            $encoded = $isAnimated ? $resized->toGif() : $resized->toWebp();
            $path = $avatarBase.$suffix.'.'.$extension;

            $this->uploadDir->put($path, $encoded);
        }
    }

    /**
     * Handle the removal of the old avatar file after a successful user save.
     * We don't place this in remove() because otherwise we would call changeAvatarPath 2 times when uploading.
     */
    protected function removeFileAfterSave(User $user): void
    {
        $avatarPath = $user->getRawOriginal('avatar_url');

        // If there was no avatar, there's nothing to remove.
        if (! $avatarPath) {
            return;
        }

        $user->afterSave(function () use ($avatarPath) {
            $this->deleteAllVariants($avatarPath);
        });
    }

    public function remove(User $user): void
    {
        $this->removeFileAfterSave($user);

        $user->changeAvatarPath(null);
    }

    /**
     * Delete the base file and all HiDPI variants (@2x, @3x) for a given base path.
     * Safe to call with external URLs — the filesystem exists() check guards against it.
     */
    public function deleteAllVariants(string $basePath): void
    {
        // Derive all variant paths from the base path (e.g. "abc.webp" → "abc@2x.webp")
        $paths = $this->variantPaths($basePath);

        foreach ($paths as $path) {
            if ($this->uploadDir->exists($path)) {
                $this->uploadDir->delete($path);
            }
        }
    }

    /**
     * Return the srcset string for a given base path, including only variants that exist on disk.
     * Returns null if only the base file exists (no HiDPI variants).
     */
    public function srcsetFor(string $basePath): ?string
    {
        // External URLs (e.g. from OAuth provider) — no local variants.
        if (str_contains($basePath, '://')) {
            return null;
        }

        // Collect which variant paths exist on disk first, without calling url().
        $existing = [];

        foreach (self::SIZES as $suffix => $size) {
            $path = $this->variantPath($basePath, $suffix);

            if ($this->uploadDir->exists($path)) {
                $existing[$path] = $size / 100;
            }
        }

        // Only meaningful if we have more than just the 1× base.
        if (count($existing) <= 1) {
            return null;
        }

        $entries = [];

        foreach ($existing as $path => $multiplier) {
            $url = $this->uploadDir->url($path);
            $entries[] = "$url {$multiplier}x";
        }

        return implode(', ', $entries);
    }

    /**
     * Return all variant paths (base + @2x + @3x) for a given base path.
     */
    protected function variantPaths(string $basePath): array
    {
        return array_map(
            fn (string $suffix) => $this->variantPath($basePath, $suffix),
            array_keys(self::SIZES)
        );
    }

    /**
     * Derive a variant path from the base path and a suffix (e.g. '' → 'abc.webp', '@2x' → 'abc@2x.webp').
     */
    protected function variantPath(string $basePath, string $suffix): string
    {
        if ($suffix === '') {
            return $basePath;
        }

        $dot = strrpos($basePath, '.');

        return $dot !== false
            ? substr($basePath, 0, $dot).$suffix.substr($basePath, $dot)
            : $basePath.$suffix;
    }
}
