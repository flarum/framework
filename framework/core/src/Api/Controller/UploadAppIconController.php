<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Admin\AppIconValidator;
use Flarum\Forum\Branding\AppIcon;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadAppIconController extends UploadImageController
{
    protected string $filePathSettingKey = 'app_icon_path';
    protected string $filenamePrefix = 'app-icon';
    protected string $fileExtension = 'png';
    protected ?string $validator = AppIconValidator::class;
    protected ?string $themeImage = 'app_icon';

    protected function makeImage(UploadedFileInterface $file): EncodedImageInterface
    {
        return $this->imageManager->read($file->getStream()->getMetadata('uri'))
            ->removeAnimation()
            ->scaleDown(AppIcon::SOURCE_SIZE, AppIcon::SOURCE_SIZE)
            ->toPng();
    }

    protected function afterStore(ServerRequestInterface $request, UploadedFileInterface $file): void
    {
        $this->container->make(AppIcon::class)->draw();
    }
}
