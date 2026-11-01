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
        static::deleted(function (AbstractModel $model) {
            $path = $model->getRawOriginal('icon_image_path');

            if ($path) {
                resolve(IconImageUploader::class)->deleteAllVariants($path);
            }
        });
    }

    public function initializeHasIconImage(): void
    {
        $this->mergeCasts(['icon_image_scales' => 'integer']);
    }

    public function getIconImageUrlAttribute(): ?string
    {
        return resolve(IconImageUploader::class)->url($this);
    }

    public function getIconImageSrcsetAttribute(): ?string
    {
        return resolve(IconImageUploader::class)->srcset($this);
    }
}
