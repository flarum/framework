<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Branding;

use Flarum\Image\IcoEncoder;
use GdImage;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;

/**
 * Draws the favicon and app icon files from the data of one source image.
 *
 * Every size is drawn from the source itself, never from a smaller copy, and a
 * source is never enlarged: a size larger than the source is left out, or the
 * source sits smaller in the middle of a larger canvas.
 */
class IconSet
{
    public const FAVICON_SIZES = [16, 32, 48];

    public const FAVICON_PNG_SIZE = 64;

    public const APP_ICON_MIN_SIZE = 512;

    /**
     * Android crops a maskable icon to a shape of its choosing, keeping only a
     * circle 80% of the icon's width, so the image is drawn inside it.
     */
    public const MASKABLE_SAFE_ZONE = 0.8;

    public function __construct(
        protected ImageManager $images
    ) {
    }

    /**
     * @return array{png: string, ico: string}
     */
    public function favicon(string $source): array
    {
        $longest = $this->longestSide($source);

        $sizes = array_values(array_filter(self::FAVICON_SIZES, fn (int $size) => $size <= $longest)) ?: [$longest];
        $pngs = [];

        foreach ($sizes as $size) {
            $pngs[$size] = $this->square($source, $size);
        }

        return [
            'png' => $this->square($source, min(self::FAVICON_PNG_SIZE, $longest)),
            'ico' => IcoEncoder::encode($pngs),
        ];
    }

    /**
     * The home screen icons, and a favicon for forums that have no other.
     *
     * @return array<string, string> file name => contents
     */
    public function appIcon(string $source, string $background): array
    {
        $favicon = $this->favicon($source);

        return [
            'apple-touch-icon.png' => $this->square($source, 180, $background),
            'icon-192.png' => $this->square($source, 192),
            'icon-512.png' => $this->square($source, 512),
            'icon-maskable-512.png' => $this->square($source, 512, $background, self::MASKABLE_SAFE_ZONE),
            'favicon.png' => $favicon['png'],
            'favicon.ico' => $favicon['ico'],
        ];
    }

    public function longestSide(string $source): int
    {
        $size = @getimagesizefromstring($source);

        return $size ? max($size[0], $size[1]) : 0;
    }

    protected function square(string $source, int $size, ?string $background = null, float $content = 1.0): string
    {
        $inner = (int) round($size * $content);

        $image = $this->images->read($source)->removeAnimation()->pad($inner, $inner, 'transparent');

        if ($background !== null || $inner !== $size) {
            $image = $this->images->create($size, $size)
                ->fill($background ?? 'transparent')
                ->place($image, 'center');
        }

        return $this->png($image);
    }

    protected function png(ImageInterface $image): string
    {
        $native = $image->core()->native();

        if (! $native instanceof GdImage) {
            return (string) $image->toPng();
        }

        imagesavealpha($native, true);

        ob_start();
        imagepng($native, null, 9);

        return (string) ob_get_clean();
    }
}
