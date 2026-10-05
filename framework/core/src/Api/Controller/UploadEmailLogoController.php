<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Admin\LogoValidator;
use Flarum\Mail\EmailLogo;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Uploads the logo shown at the top of HTML emails. It's stored as PNG (or GIF
 * when animated) rather than WebP, which many mail clients can't display.
 */
class UploadEmailLogoController extends UploadImageController
{
    protected string $filePathSettingKey = EmailLogo::PATH_KEY;
    protected string $filenamePrefix = 'email-logo';
    protected ?string $validator = LogoValidator::class;

    private string $resolvedExtension = 'png';
    private string $resolvedSize = '';

    protected function makeImage(UploadedFileInterface $file): EncodedImageInterface
    {
        [$encoded, $this->resolvedExtension, $this->resolvedSize] = $this->container->make(EmailLogo::class)->encode(
            $this->imageManager->read($file->getStream()->getMetadata('uri'))
        );

        return $encoded;
    }

    protected function afterStore(ServerRequestInterface $request, UploadedFileInterface $file): void
    {
        $this->settings->set(EmailLogo::SIZE_KEY, $this->resolvedSize);
    }

    protected function fileExtension(ServerRequestInterface $request, UploadedFileInterface $file): string
    {
        return $this->resolvedExtension;
    }
}
