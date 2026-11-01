<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\IconImage;

use Flarum\Database\AbstractModel;

/**
 * For a model whose Font Awesome icon can be replaced by an uploaded image. The
 * model's table needs a nullable `icon_image_path` string and an unsigned tiny
 * integer `icon_image_scales` defaulting to 1.
 *
 * The image's files are deleted with the model. A query that deletes rows
 * directly bypasses model events, so it leaves their files behind: delete such
 * models one by one, or delete their files with the uploader.
 *
 * @property string|null $icon_image_path
 * @property int $icon_image_scales
 * @property-read string|null $icon_image_url
 * @property-read string|null $icon_image_srcset
 *
 * @mixin AbstractModel
 */
trait HasIconImage
{
    public static function bootHasIconImage(): void
    {
        static::deleted(function (self $model) {
            $path = $model->getRawOriginal('icon_image_path');

            if ($path) {
                $model->iconImageUploader()->deleteAllVariantsAfterCommit($model, $path);
            }
        });
    }

    public function initializeHasIconImage(): void
    {
        $this->mergeCasts(['icon_image_scales' => 'integer']);
    }

    /**
     * The uploader that stores this model's image. Override it to return a
     * subclass with another box size, scales or disk.
     */
    public function iconImageUploader(): IconImageUploader
    {
        return resolve(IconImageUploader::class);
    }

    public function getIconImageUrlAttribute(): ?string
    {
        return $this->iconImageUploader()->url($this);
    }

    public function getIconImageSrcsetAttribute(): ?string
    {
        return $this->iconImageUploader()->srcset($this);
    }
}
