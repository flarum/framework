<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Intervention\Image\Interfaces\EncodedImageInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadFaviconController extends UploadImageController
{
    protected string $filePathSettingKey = 'favicon_path';
    protected string $filenamePrefix = 'favicon';

    protected function makeImage(UploadedFileInterface $file): EncodedImageInterface|StreamInterface
    {
        $this->fileExtension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);

        if ($this->fileExtension === 'ico') {
            return $file->getStream();
        }

        $encodedImage = $this->imageManager->read($file->getStream()->getMetadata('uri'))
            ->scale(64, 64)
            ->toPng();

        $this->fileExtension = 'png';

        return $encodedImage;
    }
}
