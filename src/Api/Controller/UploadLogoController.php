<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Settings\SettingsRepositoryInterface;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use League\Flysystem\FilesystemInterface;
use Psr\Http\Message\UploadedFileInterface;

class UploadLogoController extends UploadImageController
{
    /**
     * {@inheritdoc}
     */
    public function __construct(SettingsRepositoryInterface $settings, FilesystemInterface $uploadDir)
    {
        parent::__construct($settings, $uploadDir);

        $this->filenamePrefix = 'logo';
        $this->filePathSettingKey = 'logo_path';
    }

    /**
     * {@inheritdoc}
     */
    protected function makeImage(UploadedFileInterface $file): Image {
        $manager = new ImageManager();

        $encodedImage = $manager->make($file->getStream())->heighten(60, function ($constraint) {
            $constraint->upsize();
        })->encode('png');

        return $encodedImage;
    }
}
