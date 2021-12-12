<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\User;

use Flarum\Foundation\AbstractValidator;
use Flarum\Foundation\ValidationException;
use Illuminate\Validation\Factory;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\ImageManager;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Contracts\Translation\TranslatorInterface;

class AvatarValidator extends AbstractValidator
{
    /**
     * @var \Illuminate\Validation\Validator
     */
    protected $laravelValidator;

    /**
     * @var ImageManager
     */
    protected $imageManager;

    public function __construct(Factory $validator, TranslatorInterface $translator, ImageManager $imageManager)
    {
        parent::__construct($validator, $translator);

        $this->imageManager = $imageManager;
    }

    /**
     * Throw an exception if a model is not valid.
     *
     * @param array $attributes
     */
    public function assertValid(array $attributes)
    {
        $this->laravelValidator = $this->makeValidator($attributes);

        $this->assertFileRequired($attributes['avatar']);
        $this->assertFileMimes($attributes['avatar']);
        $this->assertFileSize($attributes['avatar']);
    }

    protected function assertFileRequired(UploadedFileInterface $file)
    {
        $error = $file->getError();

        if ($error !== UPLOAD_ERR_OK) {
            if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
                $this->raise('file_too_large');
            }

            if ($error === UPLOAD_ERR_NO_FILE) {
                $this->raise('required');
            }

            $this->raise('file_upload_failed');
        }
    }

    protected function assertFileMimes(UploadedFileInterface $file)
    {
        $allowedTypes = $this->getAllowedTypes();

        // Block PHP files masquerading as images
        $phpExtensions = ['php', 'php3', 'php4', 'php5', 'phtml'];
        $fileExtension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);

        if (in_array(trim(strtolower($fileExtension)), $phpExtensions)) {
            $this->raise('mimes', [':values' => implode(', ', $allowedTypes)]);
        }

        $guessedExtension = MimeTypes::getDefault()->getExtensions($file->getClientMediaType())[0] ?? null;

        if (! in_array($guessedExtension, $allowedTypes)) {
            $this->raise('mimes', [':values' => implode(', ', $allowedTypes)]);
        }

        try {
            $this->imageManager->make($file->getStream());
        } catch (NotReadableException $_e) {
            $this->raise('image');
        }
    }

    protected function assertFileSize(UploadedFileInterface $file)
    {
        $maxSize = $this->getMaxSize();

        if ($file->getSize() / 1024 > $maxSize) {
            $this->raise('max.file', [':max' => $maxSize], 'max');
        }
    }

    protected function raise($error, array $parameters = [], $rule = null)
    {
        // When we switched to intl ICU message format, the translation parameters
        // have become required to be in the format `{param}`.
        // Therefore we cannot use the translator to replace the string params.
        // We use the laravel validator to make the replacements instead.
        $message = $this->laravelValidator->makeReplacements(
            $this->translator->trans("validation.$error"),
            'avatar',
            $rule ?? $error,
            array_values($parameters)
        );

        throw new ValidationException(['avatar' => $message]);
    }

    protected function getMaxSize()
    {
        return 2048;
    }

    protected function getAllowedTypes()
    {
        return ['jpeg', 'jpg', 'png', 'bmp', 'gif'];
    }
}
