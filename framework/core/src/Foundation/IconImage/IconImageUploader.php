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
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

/**
 * Stores an image used in place of a model's Font Awesome icon (a group's or a
 * tag's, for example). The model needs the `icon_image_path` and
 * `icon_image_scales` columns; see the HasIconImage concern.
 *
 * Each upload is stored as a static WebP, scaled to fit a square box at 1x, 2x
 * and 3x without cropping, so that an image can be shown whole next to a label
 * or cropped to a badge's shape by the browser.
 *
 * A model can use a subclass, with another box size, scales or disk, by
 * overriding HasIconImage::iconImageUploader().
 */
class IconImageUploader
{
    /** The box, in pixels, the 1x image is scaled to fit. */
    protected int $baseSize = 64;

    /**
     * The variants stored for each image: each scale's file name suffix, in
     * ascending order. Scale 1 must have the empty suffix.
     *
     * @var array<int, string>
     */
    protected array $scales = [
        1 => '',
        2 => '@2x',
        3 => '@3x',
    ];

    protected string $diskName = 'flarum-icons';

    protected Cloud $disk;

    public function __construct(
        Factory $filesystemFactory,
        protected LoggerInterface $logger
    ) {
        $this->disk = $filesystemFactory->disk($this->diskName);
    }

    /**
     * Store the image's variants and point the model at them. The previous
     * image's files are deleted once the model is saved and committed.
     *
     * If the model then fails to save, call revert().
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

        try {
            foreach ($this->scales as $scale => $suffix) {
                $box = $this->baseSize * $scale;

                // Never upscale: a HiDPI variant is only worth storing if the
                // source has the pixels for it. The 1x image is always stored.
                if ($scale > 1 && $sourceWidth < $box && $sourceHeight < $box) {
                    break;
                }

                $path = $this->variantPath($basePath, $suffix);

                $encoded = (clone $image)->scaleDown($box, $box)->toWebp(strip: true);

                // A disk that isn't set to throw reports a failed write by
                // returning false.
                if (! $this->disk->put($path, (string) $encoded, StoredFiles::options($path))) {
                    throw new RuntimeException("Could not store the icon image variant [$path].");
                }

                $scales = $scale;
            }
        } catch (Throwable $e) {
            $this->deleteAllVariantsOrLog($basePath);

            throw $e;
        }

        // If an enclosing transaction rolls back, the row won't point at them.
        $model->getConnection()->afterRollBack(fn () => $this->deleteAllVariantsOrLog($basePath));

        $this->removeFilesAfterSave($model);

        $model->setAttribute('icon_image_path', $basePath);
        $model->setAttribute('icon_image_scales', $scales);
    }

    /**
     * Clear the model's image. Its files are deleted once the model is saved
     * and committed.
     */
    public function remove(AbstractModel $model): void
    {
        $this->removeFilesAfterSave($model);

        $model->setAttribute('icon_image_path', null);
        $model->setAttribute('icon_image_scales', 1);
    }

    /**
     * Undo upload() or remove() after saving the model threw. Nothing is
     * undone if the row was written anyway (a `saved` listener can throw after
     * that); otherwise an uploaded image's files are deleted and the model's
     * previous image is restored.
     */
    public function revert(AbstractModel $model): void
    {
        $path = $model->getAttribute('icon_image_path');

        if ($this->isStored($model, $path)) {
            return;
        }

        if ($path && $path !== $model->getRawOriginal('icon_image_path')) {
            $this->deleteAllVariantsOrLog($path);
        }

        $model->setAttribute('icon_image_path', $model->getRawOriginal('icon_image_path'));
        $model->setAttribute('icon_image_scales', $model->getRawOriginal('icon_image_scales') ?? 1);
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

        foreach ($this->scales as $scale => $suffix) {
            if ($scale > $scales) {
                break;
            }

            $entries[] = $this->disk->url($this->variantPath($path, $suffix)).' '.$scale.'x';
        }

        return implode(', ', $entries);
    }

    /**
     * Delete an image's base file and every variant stored with it. Returns
     * false if a file could not be deleted from a disk that doesn't throw.
     */
    public function deleteAllVariants(string $basePath): bool
    {
        $deleted = true;

        foreach ($this->scales as $suffix) {
            $path = $this->variantPath($basePath, $suffix);

            if ($this->disk->exists($path) && ! $this->disk->delete($path)) {
                $deleted = false;
            }
        }

        return $deleted;
    }

    /**
     * Delete an image's files once the current transaction commits (at once
     * outside a transaction). By then the change is stored, so a failure is
     * logged rather than thrown: the files are orphaned, nothing is broken.
     */
    public function deleteAllVariantsAfterCommit(AbstractModel $model, string $basePath): void
    {
        $model->getConnection()->afterCommit(fn () => $this->deleteAllVariantsOrLog($basePath));
    }

    /**
     * Delete an image's files as clean-up, where a failure must not replace
     * the error being handled or fail a change that is already stored.
     */
    protected function deleteAllVariantsOrLog(string $basePath): void
    {
        try {
            $deleted = $this->deleteAllVariants($basePath);
            $reason = 'the disk refused';
        } catch (Throwable $e) {
            $deleted = false;
            $reason = $e->getMessage();
        }

        if (! $deleted) {
            $this->logger->error(sprintf('Could not delete the icon image [%s]: %s', $basePath, $reason));
        }
    }

    protected function removeFilesAfterSave(AbstractModel $model): void
    {
        $previousPath = $model->getRawOriginal('icon_image_path');

        if (! $previousPath) {
            return;
        }

        $model->afterSave(function (AbstractModel $model) use ($previousPath) {
            // The callback outlives a failed save; revert() then restores
            // the previous path, which must not be deleted on a later save.
            if ($model->getAttribute('icon_image_path') !== $previousPath) {
                $this->deleteAllVariantsAfterCommit($model, $previousPath);
            }
        });
    }

    /**
     * Whether the model's row in the database already holds the path.
     */
    protected function isStored(AbstractModel $model, ?string $path): bool
    {
        if (! $model->exists || $path === $model->getRawOriginal('icon_image_path')) {
            return false;
        }

        try {
            return $model->newQueryWithoutScopes()
                ->whereKey($model->getKey())
                ->value('icon_image_path') === $path;
        } catch (Throwable) {
            // Unknown: keep the files. Orphaned files are better than a
            // row pointing at deleted ones.
            return true;
        }
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
