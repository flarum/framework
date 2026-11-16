<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Filesystem\Filesystem;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\Attributes\Test;

class AppIconUploadTest extends TestCase
{
    /** @var string[] */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    /**
     * A red image, transparent where $transparentBorder says, so that padding
     * and backgrounds show in the stored pixels.
     */
    private function pngFile(int $width, int $height, int $transparentBorder = 0): string
    {
        $path = tempnam(sys_get_temp_dir(), 'flarum-app-icon-').'.png';
        $this->tempFiles[] = $path;

        $image = imagecreatetruecolor($width, $height);
        imagesavealpha($image, true);
        imagealphablending($image, false);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagefilledrectangle($image, $transparentBorder, $transparentBorder, $width - 1 - $transparentBorder, $height - 1 - $transparentBorder, imagecolorallocatealpha($image, 255, 0, 0, 0));
        imagepng($image, $path);

        return $path;
    }

    private function upload(string $path)
    {
        return $this->send(
            $this->request('POST', '/api/app-icon', ['authenticatedAs' => 1])
                ->withUploadedFiles(['app-icon' => new UploadedFile(new Stream($path), filesize($path), UPLOAD_ERR_OK, 'icon.png', 'image/png')])
        );
    }

    private function assets(): Filesystem
    {
        return $this->app()->getContainer()->make('filesystem')->disk('flarum-assets');
    }

    private function icon(string $name): \GdImage
    {
        $paths = json_decode((string) $this->database()->table('settings')->where('key', 'app_icon_paths')->value('value'), true);

        return imagecreatefromstring($this->assets()->get($paths[$name]));
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int} red, green, blue, and alpha from 0 (opaque) to 127
     */
    private function pixel(\GdImage $image, int $x, int $y): array
    {
        $color = imagecolorat($image, $x, $y);

        return [($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF, ($color >> 24) & 0x7F];
    }

    #[Test]
    public function an_app_icon_is_drawn_at_every_home_screen_size(): void
    {
        $response = $this->upload($this->pngFile(1024, 1024));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        foreach (['apple-touch-icon.png' => 180, 'icon-192.png' => 192, 'icon-512.png' => 512, 'icon-maskable-512.png' => 512] as $name => $size) {
            $icon = $this->icon($name);

            $this->assertSame([$size, $size], [imagesx($icon), imagesy($icon)], $name);
        }
    }

    #[Test]
    public function the_apple_touch_icon_is_opaque_on_the_background_colour(): void
    {
        $this->upload($this->pngFile(1024, 1024, transparentBorder: 100));

        $this->assertSame([255, 255, 255, 0], $this->pixel($this->icon('apple-touch-icon.png'), 2, 2));
        $this->assertSame(127, $this->pixel($this->icon('icon-512.png'), 2, 2)[3]);
    }

    #[Test]
    public function the_maskable_icon_keeps_the_image_inside_the_safe_zone(): void
    {
        $this->upload($this->pngFile(1024, 1024));

        $icon = $this->icon('icon-maskable-512.png');

        $this->assertSame([255, 255, 255, 0], $this->pixel($icon, 40, 256));
        $this->assertSame([255, 0, 0, 0], $this->pixel($icon, 60, 256));
        $this->assertSame([255, 0, 0, 0], $this->pixel($icon, 256, 256));
    }

    #[Test]
    public function a_wide_image_is_padded_into_a_square_not_stretched(): void
    {
        $this->upload($this->pngFile(1024, 512));

        $icon = $this->icon('icon-512.png');

        $this->assertSame(127, $this->pixel($icon, 256, 20)[3]);
        $this->assertSame([255, 0, 0, 0], $this->pixel($icon, 256, 256));
    }

    #[Test]
    public function an_app_icon_smaller_than_512px_is_refused(): void
    {
        $response = $this->upload($this->pngFile(300, 300));

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertSame('/data/attributes/app-icon', json_decode((string) $response->getBody(), true)['errors'][0]['source']['pointer']);
        $this->assertNull($this->database()->table('settings')->where('key', 'app_icon_path')->value('value'));
    }

    #[Test]
    public function changing_the_background_colour_redraws_the_icons(): void
    {
        $this->upload($this->pngFile(1024, 1024, transparentBorder: 100));

        $response = $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => ['app_icon_background' => '#000000'],
            ])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame([0, 0, 0, 0], $this->pixel($this->icon('apple-touch-icon.png'), 2, 2));
    }

    #[Test]
    public function the_app_icon_is_the_favicon_when_none_is_uploaded(): void
    {
        $this->upload($this->pngFile(1024, 1024));

        $paths = json_decode((string) $this->database()->table('settings')->where('key', 'app_icon_paths')->value('value'), true);
        $html = (string) $this->send($this->request('GET', '/'))->getBody();

        $this->assertStringContainsString('<link rel="icon" href="'.$this->assets()->url($paths['favicon.ico']).'" sizes="32x32">', $html);
        $this->assertStringContainsString('<link rel="apple-touch-icon" href="'.$this->assets()->url($paths['apple-touch-icon.png']).'">', $html);
    }

    #[Test]
    public function removing_the_app_icon_removes_every_size(): void
    {
        $this->upload($this->pngFile(1024, 1024));

        $paths = json_decode((string) $this->database()->table('settings')->where('key', 'app_icon_paths')->value('value'), true);
        $source = $this->database()->table('settings')->where('key', 'app_icon_path')->value('value');

        $response = $this->send($this->request('DELETE', '/api/app-icon', ['authenticatedAs' => 1]));

        $this->assertEquals(204, $response->getStatusCode());

        foreach ([$source, ...array_values($paths)] as $path) {
            $this->assertFalse($this->assets()->exists($path), $path);
        }

        $this->assertNull($this->database()->table('settings')->where('key', 'app_icon_paths')->value('value'));
    }
}
