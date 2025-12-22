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
use Flarum\Locale\TranslatorInterface;
use Illuminate\Validation\Factory;
use Intervention\Gif\Exceptions\DecoderException as GifDecoderException;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\ImageManager;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Mime\MimeTypes;

class AvatarValidator extends AbstractValidator
{
    public function __construct(
        Factory $validator,
        TranslatorInterface $translator,
        protected ImageManager $imageManager
    ) {
        parent::__construct($validator, $translator);
    }

    public function assertValid(array $attributes): void
    {
        $this->laravelValidator = $this->makeValidator($attributes);

        $this->assertFileRequired($attributes['avatar']);
        $this->assertFileMimes($attributes['avatar']);
        $this->assertFileSize($attributes['avatar']);
    }

    protected function assertFileRequired(UploadedFileInterface $file): void
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

    protected function assertFileMimes(UploadedFileInterface $file): void
    {
        $allowedTypes = $this->getAllowedTypes();

        // Block PHP files masquerading as images
        $phpExtensions = ['php', 'php3', 'php4', 'php5', 'phtml'];
        $fileExtension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);

        if (in_array(strtolower(trim($fileExtension)), $phpExtensions)) {
            $this->raise('mimes', [':values' => implode(', ', $allowedTypes)]);
        }

        $guessedExtension = MimeTypes::getDefault()->getExtensions($file->getClientMediaType())[0] ?? null;

        if (! in_array($guessedExtension, $allowedTypes)) {
            $this->raise('mimes', [':values' => implode(', ', $allowedTypes)]);
        }

        try {
            $this->imageManager->read($file->getStream()->getMetadata('uri'));
        } catch (DecoderException|GifDecoderException) {
            $this->raise('image');
        }
    }

    protected function assertFileSize(UploadedFileInterface $file): void
    {
        $maxSize = $this->getMaxSize();

        if ($file->getSize() / 1024 > $maxSize) {
            $this->raise('max.file', [':max' => $maxSize], 'max');
        }
    }

    protected function raise(string $error, array $parameters = [], ?string $rule = null): void
    {
        // When we switched to intl ICU message format, the translation parameters
        // have become required to be in the format `{param}`.
        // Therefore, we cannot use the translator to replace the string params.
        // We use the laravel validator to make the replacements instead.
        $message = $this->laravelValidator->makeReplacements(
            $this->translator->trans("validation.$error"),
            'avatar',
            $rule ?? $error,
            array_values($parameters)
        );

        throw new ValidationException(['avatar' => $message]);
    }

    protected function getMaxSize(): int
    {
        return 2048;
    }

    protected function getAllowedTypes(): array
    {
        return ['jpeg', 'jpg', 'png', 'bmp', 'gif'];
    }
}
