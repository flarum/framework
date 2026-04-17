<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Admin\LogoValidator;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadLogoController extends UploadImageController
{
    protected string $filePathSettingKey = 'logo_path';
    protected string $filenamePrefix = 'logo';
    private string $resolvedExtension = 'webp';
    protected ?string $validator = LogoValidator::class;

    protected function makeImage(UploadedFileInterface $file): EncodedImageInterface
    {
        $image = $this->imageManager->read($file->getStream()->getMetadata('uri'))
            ->scale(height: 60);

        if ($image->isAnimated()) {
            $this->resolvedExtension = 'gif';

            return $image->toGif();
        }

        $this->resolvedExtension = 'webp';

        return $image->toWebp();
    }

    protected function fileExtension(ServerRequestInterface $request, UploadedFileInterface $file): string
    {
        return $this->resolvedExtension;
    }
}
