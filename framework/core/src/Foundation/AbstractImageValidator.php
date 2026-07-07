<?php

declare(strict_types=1);

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Flarum\Locale\TranslatorInterface;
use Illuminate\Validation\Factory;
use Intervention\Gif\Exceptions\DecoderException as GifDecoderException;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\ImageManager;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\Mime\MimeTypes;

abstract class AbstractImageValidator extends AbstractValidator
{
    protected string $filename;

    public function __construct(
        Factory $validator,
        TranslatorInterface $translator,
        protected ImageManager $imageManager
    ) {
        parent::__construct($validator, $translator);
    }

    public function assertImageValid(string $filename, UploadedFileInterface $file): void
    {
        $this->filename = $filename;
        $this->assertValid([$filename => $file]);
    }

    public function assertValid(array $attributes): void
    {
        $this->laravelValidator = $this->makeValidator($attributes);

        $this->assertFileRequired($attributes[$this->filename]);
        // Check the (compressed) byte size before decoding the image, so an
        // oversized upload is rejected without ever being read into memory.
        $this->assertFileSize($attributes[$this->filename]);
        $this->assertFileMimes($attributes[$this->filename]);
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

        // Reject images whose pixel dimensions exceed the maximum resolution
        // *before* decoding them. Image libraries (e.g. GD) allocate a buffer
        // sized to width * height from the header, regardless of the compressed
        // byte size, so a tiny "decompression bomb" declaring huge dimensions
        // could otherwise exhaust the available memory during the decode below.
        $dimensions = @getimagesizefromstring((string) $file->getStream());

        if ($dimensions === false) {
            $this->raise('image');

            return;
        }

        if ($dimensions[0] * $dimensions[1] > $this->getMaxResolution()) {
            $this->raise('dimensions');
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
        $size = $file->getSize();

        // A null size means the size could not be determined; reject it rather
        // than letting it bypass the cap (null / 1024 > $maxSize is false).
        if ($size === null || $size / 1024 > $maxSize) {
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
            $this->filename,
            $rule ?? $error,
            array_values($parameters)
        );

        throw new ValidationException([$this->filename => $message]);
    }

    public function getMaxSize(): int
    {
        return 2048;
    }

    /**
     * The maximum number of pixels (width * height) allowed in an uploaded
     * image. This bounds the memory a single decode can allocate, guarding
     * against decompression-bomb uploads that declare huge dimensions in a
     * tiny file. ~24 megapixels comfortably covers legitimate photos while
     * rejecting pathological dimensions.
     */
    public function getMaxResolution(): int
    {
        return 24_000_000;
    }

    protected function getAllowedTypes(): array
    {
        return ['jpeg', 'jpg', 'png', 'bmp', 'gif', 'webp'];
    }
}
