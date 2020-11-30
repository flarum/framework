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

class UploadFaviconController extends UploadImageController
{
    /**
     * {@inheritdoc}
     */
    public function __construct(SettingsRepositoryInterface $settings, FilesystemInterface $uploadDir)
    {
        parent::__construct($settings, $uploadDir);

        $this->filenamePrefix = 'favicon';
        $this->filePathSettingKey = 'favicon_path';
    }

    /**
     * {@inheritdoc}
     */
    protected function makeImage(UploadedFileInterface $file): Image
    {
        $this->fileExtension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);

        if ($this->fileExtension === 'ico') {
            $encodedImage = $file->getStream();
        } else {
            $manager = new ImageManager();

            $encodedImage = $manager->make($file->getStream())->resize(64, 64, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })->encode('png');

            $this->fileExtension = 'png';
        }

        return $encodedImage;
    }
}
