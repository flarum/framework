<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\unit\Image;

use Flarum\Image\IcoEncoder;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;

class IcoEncoderTest extends TestCase
{
    private function png(int $size): string
    {
        $image = imagecreatetruecolor($size, $size);
        imagefill($image, 0, 0, imagecolorallocate($image, $size % 256, 0, 0));

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    /**
     * @return list<array{width: int, height: int, data: string}>
     */
    private function entries(string $ico): array
    {
        $header = unpack('vreserved/vtype/vcount', $ico);
        $entries = [];

        for ($i = 0; $i < $header['count']; $i++) {
            $entry = unpack('Cwidth/Cheight/Ccolors/Creserved/vplanes/vbits/Vsize/Voffset', $ico, 6 + 16 * $i);

            $entries[] = [
                'width' => $entry['width'] ?: 256,
                'height' => $entry['height'] ?: 256,
                'data' => substr($ico, $entry['offset'], $entry['size']),
            ];
        }

        return $entries;
    }

    #[Test]
    public function it_packs_each_png_into_an_icon_browsers_can_read(): void
    {
        $pngs = [16 => $this->png(16), 32 => $this->png(32), 48 => $this->png(48)];

        $ico = IcoEncoder::encode($pngs);

        $this->assertSame([0, 1, 3], array_values(unpack('v3', $ico)));
        $this->assertSame(IMAGETYPE_ICO, getimagesizefromstring($ico)[2]);

        foreach ($this->entries($ico) as $entry) {
            $this->assertSame($pngs[$entry['width']], $entry['data']);
            $this->assertSame($entry['width'], $entry['height']);
        }
    }

    #[Test]
    public function a_256px_image_is_recorded_as_zero(): void
    {
        $ico = IcoEncoder::encode([256 => $this->png(256)]);

        $this->assertSame(0, ord($ico[6]));
        $this->assertSame(256, $this->entries($ico)[0]['width']);
    }
}
