<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Psr\Http\Message\UploadedFileInterface;

class UploadLogoController extends UploadImageController
{
    protected string $filePathSettingKey = 'logo_path';
    protected string $filenamePrefix = 'logo';

    public function __construct(
        SettingsRepositoryInterface $settings,
        Factory $filesystemFactory,
        protected ImageManager $imageManager
    ) {
        parent::__construct($settings, $filesystemFactory);
    }

    protected function makeImage(UploadedFileInterface $file): Image
    {
        $encodedImage = $this->imageManager->make($file->getStream()->getMetadata('uri'))->heighten(60, function ($constraint) {
            $constraint->upsize();
        })->encode('png');

        return $encodedImage;
    }
}
