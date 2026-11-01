<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\IconImage;

use Flarum\Database\AbstractModel;
use Flarum\Forum\Branding\StoredFiles;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\Str;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Stores an image used in place of a model's Font Awesome icon (a group's or a
 * tag's, for example). The model needs the `icon_image_path` and
 * `icon_image_scales` columns; see the HasIconImage concern.
 *
 * Each upload is stored as a static WebP, scaled to fit a square box at 1x, 2x
 * and 3x without cropping, so that an image can be shown whole next to a label
 * or cropped to a badge's shape by the browser.
 */
class IconImageUploader
{
    /** The box, in pixels, the 1x image is scaled to fit. */
    public const BASE_SIZE = 64;

    /** The variants stored for each image, keyed by their scale. */
    protected const SCALES = [
        1 => '',
        2 => '@2x',
        3 => '@3x',
    ];

    protected Cloud $disk;

    public function __construct(Factory $filesystemFactory)
    {
        $this->disk = $filesystemFactory->disk('flarum-icons');
    }

    /**
     * Store the image's variants and point the model at them. The previous
     * image's files are deleted once the model is saved.
     */
    public function upload(AbstractModel $model, ImageInterface $image): void
    {
        // Icons are static: an animated image keeps only its first frame.
        if ($image->isAnimated()) {
            $image->removeAnimation(0);
        }

        $sourceWidth = $image->width();
        $sourceHeight = $image->height();

        $basePath = Str::lower(Str::random(20)).'.webp';
        $scales = 1;

        foreach (self::SCALES as $scale => $suffix) {
            $box = self::BASE_SIZE * $scale;

            // Never upscale: a HiDPI variant is only worth storing if the source
            // has the pixels for it. The 1x image is always stored.
            if ($scale > 1 && $sourceWidth < $box && $sourceHeight < $box) {
                break;
            }

            $path = $this->variantPath($basePath, $suffix);

            $encoded = (clone $image)->scaleDown($box, $box)->toWebp(strip: true);

            $this->disk->put($path, (string) $encoded, StoredFiles::options($path));

            $scales = $scale;
        }

        $this->removeFilesAfterSave($model);

        $model->setAttribute('icon_image_path', $basePath);
        $model->setAttribute('icon_image_scales', $scales);
    }

    /**
     * Clear the model's image. Its files are deleted once the model is saved.
     */
    public function remove(AbstractModel $model): void
    {
        $this->removeFilesAfterSave($model);

        $model->setAttribute('icon_image_path', null);
        $model->setAttribute('icon_image_scales', 1);
    }

    public function url(AbstractModel $model): ?string
    {
        $path = $model->getAttribute('icon_image_path');

        return $path ? $this->disk->url($path) : null;
    }

    /**
     * The srcset for the model's image, built from the stored scale count
     * rather than by checking the disk. Null when there are no HiDPI variants.
     */
    public function srcset(AbstractModel $model): ?string
    {
        $path = $model->getAttribute('icon_image_path');
        $scales = (int) $model->getAttribute('icon_image_scales');

        if (! $path || $scales < 2) {
            return null;
        }

        $entries = [];

        foreach (self::SCALES as $scale => $suffix) {
            if ($scale > $scales) {
                break;
            }

            $entries[] = $this->disk->url($this->variantPath($path, $suffix)).' '.$scale.'x';
        }

        return implode(', ', $entries);
    }

    /**
     * Delete an image's base file and every variant stored with it.
     */
    public function deleteAllVariants(string $basePath): void
    {
        foreach (self::SCALES as $suffix) {
            $path = $this->variantPath($basePath, $suffix);

            if ($this->disk->exists($path)) {
                $this->disk->delete($path);
            }
        }
    }

    protected function removeFilesAfterSave(AbstractModel $model): void
    {
        $previousPath = $model->getRawOriginal('icon_image_path');

        if (! $previousPath) {
            return;
        }

        $model->afterSave(function () use ($previousPath) {
            $this->deleteAllVariants($previousPath);
        });
    }

    /**
     * The path of a variant: 'abc.webp' with '@2x' is 'abc@2x.webp'.
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
