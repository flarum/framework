<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Flarum\Extend;
use Flarum\Testing\integration\TestCase;
use Intervention\Image\ImageManager;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class LogoUploadTest extends TestCase
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
     * A noisy image, so that the encoding quality shows in the stored bytes.
     */
    private function pngFile(int $width, int $height): string
    {
        $path = tempnam(sys_get_temp_dir(), 'flarum-logo-').'.png';
        $this->tempFiles[] = $path;

        mt_srand(1);

        $image = imagecreatetruecolor($width, $height);

        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                imagesetpixel($image, $x, $y, imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
            }
        }

        imagepng($image, $path);

        return $path;
    }

    private function upload(string $endpoint, string $path, string $name = 'logo.png', string $type = 'image/png'): string
    {
        $response = $this->send(
            $this->request('POST', "/api/$endpoint", ['authenticatedAs' => 1])
                ->withUploadedFiles([$endpoint => new UploadedFile(new Stream($path), filesize($path), UPLOAD_ERR_OK, $name, $type)])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $key = $endpoint === 'logo' ? 'logo_path' : 'logo_dark_mode_path';

        return $this->app()->getContainer()->make('filesystem')->disk('flarum-assets')->get(
            $this->database()->table('settings')->where('key', $key)->value('value')
        );
    }

    private function webp(string $path, int $height, int $quality): string
    {
        return (string) $this->app()->getContainer()->make(ImageManager::class)
            ->read($path)
            ->scale(height: $height)
            ->toWebp(quality: $quality);
    }

    public static function endpoints(): array
    {
        return [
            'logo' => ['logo'],
            'dark mode logo' => ['logo-dark-mode'],
        ];
    }

    #[Test]
    #[DataProvider('endpoints')]
    public function a_logo_is_stored_90px_tall_as_webp_at_quality_90(string $endpoint): void
    {
        $source = $this->pngFile(800, 200);

        $stored = $this->upload($endpoint, $source);

        $this->assertEquals([360, 90, IMAGETYPE_WEBP], array_slice(getimagesizefromstring($stored), 0, 3));
        $this->assertNotEquals($this->webp($source, 90, 75), $this->webp($source, 90, 90));
        $this->assertTrue($stored === $this->webp($source, 90, 90), 'The logo is not encoded at quality 90.');
    }

    #[Test]
    #[DataProvider('endpoints')]
    public function an_extension_can_store_the_logo_taller_and_at_another_quality(string $endpoint): void
    {
        $this->extend(
            (new Extend\Theme())->logo(height: 120, quality: 50)
        );

        $source = $this->pngFile(800, 200);

        $stored = $this->upload($endpoint, $source);

        $this->assertEquals([480, 120], array_slice(getimagesizefromstring($stored), 0, 2));
        $this->assertTrue($stored === $this->webp($source, 120, 50), 'The logo is not encoded at quality 50.');
    }

    #[Test]
    public function setting_only_the_quality_keeps_the_default_height(): void
    {
        $this->extend(
            (new Extend\Theme())->logo(quality: 50)
        );

        $source = $this->pngFile(800, 200);

        $stored = $this->upload('logo', $source);

        $this->assertEquals([360, 90], array_slice(getimagesizefromstring($stored), 0, 2));
        $this->assertTrue($stored === $this->webp($source, 90, 50), 'The logo is not encoded at quality 50.');
    }

    #[Test]
    public function setting_only_the_height_keeps_the_default_quality(): void
    {
        $this->extend(
            (new Extend\Theme())->logo(height: 120)
        );

        $source = $this->pngFile(800, 200);

        $stored = $this->upload('logo', $source);

        $this->assertTrue($stored === $this->webp($source, 120, 90), 'The logo is not encoded at quality 90.');
    }

    #[Test]
    public function an_animated_logo_stays_a_gif_at_the_logo_height(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'flarum-logo-').'.gif';
        $this->tempFiles[] = $path;

        $frames = [$this->pngFile(400, 100), $this->pngFile(400, 100)];

        $this->app()->getContainer()->make(ImageManager::class)
            ->animate(function ($animation) use ($frames) {
                foreach ($frames as $frame) {
                    $animation->add($frame, 0.5);
                }
            })
            ->toGif()
            ->save($path);

        $stored = $this->upload('logo', $path, 'logo.gif', 'image/gif');

        $this->assertEquals([360, 90, IMAGETYPE_GIF], array_slice(getimagesizefromstring($stored), 0, 3));
    }
}
