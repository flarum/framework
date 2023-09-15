<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Foundation\ValidationException;
use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Factory;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Psr\Http\Message\UploadedFileInterface;

class UploadFaviconController extends UploadImageController
{
    protected string $filePathSettingKey = 'favicon_path';
    protected string $filenamePrefix = 'favicon';

    public function __construct(
        SettingsRepositoryInterface $settings,
        Factory $filesystemFactory,
        protected TranslatorInterface $translator,
        protected ImageManager $imageManager
    ) {
        parent::__construct($settings, $filesystemFactory);
    }

    protected function makeImage(UploadedFileInterface $file): Image
    {
        $this->fileExtension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);

        if ($this->fileExtension === 'ico') {
            // @todo remove in 2.0
            throw new ValidationException([
                'message' => strtr($this->translator->trans('validation.mimes'), [
                    ':attribute' => 'favicon',
                    ':values' => 'jpeg,png,gif,webp',
                ])
            ]);
        }

        $encodedImage = $this->imageManager->make($file->getStream()->getMetadata('uri'))->resize(64, 64, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->encode('png');

        $this->fileExtension = 'png';

        return $encodedImage;
    }
}
