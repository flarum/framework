<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Flarum\Image\IcoEncoder;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Filesystem\Filesystem;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\Attributes\Test;

class FaviconUploadTest extends TestCase
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

    private function pngFile(int $width, int $height): string
    {
        $path = tempnam(sys_get_temp_dir(), 'flarum-favicon-').'.png';
        $this->tempFiles[] = $path;

        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 30, 60));
        imagepng($image, $path);

        return $path;
    }

    private function upload(string $path, string $name = 'favicon.png', string $type = 'image/png')
    {
        return $this->send(
            $this->request('POST', '/api/favicon', ['authenticatedAs' => 1])
                ->withUploadedFiles(['favicon' => new UploadedFile(new Stream($path), filesize($path), UPLOAD_ERR_OK, $name, $type)])
        );
    }

    private function stored(string $key): ?string
    {
        return $this->database()->table('settings')->where('key', $key)->value('value');
    }

    private function assets(): Filesystem
    {
        return $this->app()->getContainer()->make('filesystem')->disk('flarum-assets');
    }

    /**
     * @return list<int>
     */
    private function icoSizes(string $ico): array
    {
        $sizes = [];

        for ($i = 0; $i < unpack('v', $ico, 4)[1]; $i++) {
            $sizes[] = ord($ico[6 + 16 * $i]) ?: 256;
        }

        return $sizes;
    }

    #[Test]
    public function a_favicon_also_gets_an_ico_with_16_32_and_48px_icons(): void
    {
        $response = $this->upload($this->pngFile(128, 128));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertStringEndsWith('.ico', $this->stored('favicon_ico_path'));
        $this->assertSame([16, 32, 48], $this->icoSizes($this->assets()->get($this->stored('favicon_ico_path'))));
    }

    #[Test]
    public function a_small_favicon_is_never_enlarged(): void
    {
        $this->upload($this->pngFile(24, 24));

        $this->assertSame([16], $this->icoSizes($this->assets()->get($this->stored('favicon_ico_path'))));
        $this->assertSame([24, 24], array_slice(getimagesizefromstring($this->assets()->get($this->stored('favicon_path'))), 0, 2));
    }

    #[Test]
    public function the_page_links_the_ico_as_its_icon(): void
    {
        $this->upload($this->pngFile(128, 128));

        $html = (string) $this->send($this->request('GET', '/'))->getBody();

        $this->assertStringContainsString('<link rel="icon" href="'.$this->assets()->url($this->stored('favicon_ico_path')).'" sizes="32x32">', $html);
    }

    #[Test]
    public function an_ico_file_can_be_uploaded_as_the_favicon(): void
    {
        $png = file_get_contents($this->pngFile(32, 32));
        $path = tempnam(sys_get_temp_dir(), 'flarum-favicon-').'.ico';
        $this->tempFiles[] = $path;
        file_put_contents($path, IcoEncoder::encode([32 => $png]));

        $response = $this->upload($path, 'favicon.ico', 'image/x-icon');

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertStringEndsWith('.ico', $this->stored('favicon_path'));
        $this->assertStringContainsString(
            '<link rel="icon" href="'.$this->assets()->url($this->stored('favicon_path')).'" sizes="32x32">',
            (string) $this->send($this->request('GET', '/'))->getBody()
        );
    }

    #[Test]
    public function removing_the_favicon_removes_the_ico_too(): void
    {
        $this->upload($this->pngFile(128, 128));
        $ico = $this->stored('favicon_ico_path');

        $response = $this->send($this->request('DELETE', '/api/favicon', ['authenticatedAs' => 1]));

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertNull($this->stored('favicon_ico_path'));
        $this->assertFalse($this->assets()->exists($ico));
    }
}
