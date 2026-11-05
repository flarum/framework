<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\IconImage;

use Flarum\Foundation\AbstractImageValidator;
use Intervention\Gif\Exceptions\DecoderException as GifDecoderException;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\Interfaces\ImageInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Raster images only: an SVG served from the forum's own domain could run
 * script, and GD cannot read it.
 */
class IconImageValidator extends AbstractImageValidator
{
    protected ?ImageInterface $image = null;

    /**
     * Validate the upload and return it decoded, so that it is only decoded
     * once.
     */
    public function validateAndRead(string $filename, UploadedFileInterface $file): ImageInterface
    {
        $this->image = null;

        $this->assertImageValid($filename, $file);

        // A subclass's assertDecodable() may not have kept the image.
        return $this->image ?? $this->read($file);
    }

    protected function assertDecodable(UploadedFileInterface $file): void
    {
        $this->image = $this->read($file);
    }

    protected function read(UploadedFileInterface $file): ImageInterface
    {
        try {
            return $this->imageManager->read($file->getStream()->getMetadata('uri'));
        } catch (DecoderException|GifDecoderException $e) {
            $this->raise('image');

            throw $e; // raise() always throws.
        }
    }
}
