<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\EncodedImageInterface;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * The logo shown at the top of HTML emails.
 *
 * The forum logo is stored as WebP, which many mail clients (Outlook on
 * Windows among them) can't display. Emails therefore use a PNG (or, for an
 * animated logo, GIF) image instead: an email logo the admin uploaded, or a
 * copy made automatically whenever the forum logo is uploaded.
 *
 * Each image's pixel size is stored alongside its path, so the email can carry
 * explicit width and height attributes — Outlook ignores CSS sizing — without
 * reading the file from the assets disk every time a message is sent.
 */
class EmailLogo
{
    /** The height, in CSS pixels, the logo is shown at in emails. */
    public const DISPLAY_HEIGHT = 60;

    /** The widest the logo is shown, in CSS pixels: the 600px email body less its padding. */
    public const MAX_DISPLAY_WIDTH = 560;

    /** Images are stored at twice their display size so they stay sharp on HiDPI screens. */
    protected const SCALE = 2;

    public const PATH_KEY = 'email_logo_path';
    public const SIZE_KEY = 'email_logo_size';
    public const COPY_PATH_KEY = 'logo_email_copy_path';
    public const COPY_SIZE_KEY = 'logo_email_copy_size';

    protected Cloud $assetsDir;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected ImageManager $imageManager,
        Factory $filesystemFactory
    ) {
        $this->assetsDir = $filesystemFactory->disk('flarum-assets');
    }

    /**
     * Scale an image down to the stored email size and encode it in a format
     * mail clients can display. Never enlarges the image.
     *
     * @return array{0: EncodedImageInterface, 1: string, 2: string} The encoded image, its file extension, and its size as "WIDTHxHEIGHT".
     */
    public function encode(ImageInterface $image): array
    {
        // One dimension at a time: each call keeps the aspect ratio.
        $image->scaleDown(height: self::DISPLAY_HEIGHT * self::SCALE);
        $image->scaleDown(width: self::MAX_DISPLAY_WIDTH * self::SCALE);

        $size = $image->width().'x'.$image->height();

        if ($image->isAnimated()) {
            return [$image->toGif(), 'gif', $size];
        }

        return [$image->toPng(), 'png', $size];
    }

    /**
     * Store an email-safe copy of a newly uploaded forum logo, replacing any
     * previous copy.
     */
    public function storeCopy(string $sourcePath): void
    {
        [$encoded, $extension, $size] = $this->encode($this->imageManager->read($sourcePath));

        $this->deleteCopyFile();

        $path = 'logo-email-'.Str::lower(Str::random(8)).'.'.$extension;

        $this->assetsDir->put($path, $encoded);

        $this->settings->set(self::COPY_PATH_KEY, $path);
        $this->settings->set(self::COPY_SIZE_KEY, $size);
    }

    public function deleteCopy(): void
    {
        $this->deleteCopyFile();

        $this->settings->set(self::COPY_PATH_KEY, null);
        $this->settings->set(self::COPY_SIZE_KEY, null);
    }

    protected function deleteCopyFile(): void
    {
        $path = $this->settings->get(self::COPY_PATH_KEY);

        if ($path && $this->assetsDir->exists($path)) {
            $this->assetsDir->delete($path);
        }
    }

    /**
     * The logo to show in emails, if there is one a mail client can display.
     *
     * In order: the uploaded email logo; the copy of the forum logo; the forum
     * logo itself, but only if it isn't WebP (logos uploaded before they were
     * converted to WebP are PNG, JPEG or GIF). The width and height are null
     * when the image's size isn't known.
     *
     * @return array{url: string, width: int|null, height: int|null}|null
     */
    public function resolve(): ?array
    {
        foreach ([[self::PATH_KEY, self::SIZE_KEY], [self::COPY_PATH_KEY, self::COPY_SIZE_KEY]] as [$pathKey, $sizeKey]) {
            if ($path = $this->settings->get($pathKey)) {
                return ['url' => $this->assetsDir->url($path)] + $this->displaySize($this->settings->get($sizeKey));
            }
        }

        $logoPath = $this->settings->get('logo_path');

        if ($logoPath && strtolower(pathinfo($logoPath, PATHINFO_EXTENSION)) !== 'webp') {
            return ['url' => $this->assetsDir->url($logoPath), 'width' => null, 'height' => null];
        }

        return null;
    }

    /**
     * The size, in CSS pixels, to show an image of the given stored size at.
     *
     * @return array{width: int|null, height: int|null}
     */
    protected function displaySize(?string $storedSize): array
    {
        if (! $storedSize || ! preg_match('/^(\d+)x(\d+)$/', $storedSize, $matches) || ! (int) $matches[1] || ! (int) $matches[2]) {
            return ['width' => null, 'height' => null];
        }

        [, $width, $height] = array_map('intval', $matches);

        $displayHeight = self::DISPLAY_HEIGHT;
        $displayWidth = (int) round($width * $displayHeight / $height);

        if ($displayWidth > self::MAX_DISPLAY_WIDTH) {
            $displayWidth = self::MAX_DISPLAY_WIDTH;
            $displayHeight = max(1, (int) round($height * $displayWidth / $width));
        }

        return ['width' => $displayWidth, 'height' => $displayHeight];
    }
}
