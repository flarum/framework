<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Intervention\Image\ImageManager;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class EmailLogoUploadTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    /** @var string[] */
    private array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase([
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }

        parent::tearDown();
    }

    private function pngFile(int $width, int $height): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'flarum-logo-').'.png';
        $this->tempFiles[] = $path;

        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 80, 40));
        imagepng($image, $path);

        return new UploadedFile(new Stream($path), filesize($path), UPLOAD_ERR_OK, 'logo.png', 'image/png');
    }

    private function upload(string $endpoint, UploadedFile $file, int $actorId = 1): ResponseInterface
    {
        return $this->send(
            $this->request('POST', "/api/$endpoint", ['authenticatedAs' => $actorId])
                ->withUploadedFiles([$endpoint => $file])
        );
    }

    private function delete(string $endpoint): ResponseInterface
    {
        return $this->send($this->request('DELETE', "/api/$endpoint", ['authenticatedAs' => 1]));
    }

    private function storedSetting(string $key): ?string
    {
        return $this->database()->table('settings')->where('key', $key)->value('value');
    }

    private function assets(): Filesystem
    {
        return $this->app()->getContainer()->make('filesystem')->disk('flarum-assets');
    }

    #[Test]
    public function uploading_the_logo_stores_a_png_copy_for_emails(): void
    {
        $response = $this->upload('logo', $this->pngFile(800, 200));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        // The forum logo itself is unchanged: WebP, 60px tall.
        $this->assertStringEndsWith('.webp', $this->storedSetting('logo_path'));

        $copyPath = $this->storedSetting('logo_email_copy_path');

        $this->assertNotNull($copyPath);
        $this->assertStringEndsWith('.png', $copyPath);
        $this->assertTrue($this->assets()->exists($copyPath));
        $this->assertEquals('480x120', $this->storedSetting('logo_email_copy_size'));
        $this->assertEquals([480, 120], array_slice(getimagesizefromstring($this->assets()->get($copyPath)), 0, 2));
    }

    #[Test]
    public function uploading_the_logo_again_replaces_the_copy(): void
    {
        $this->upload('logo', $this->pngFile(800, 200));
        $firstCopy = $this->storedSetting('logo_email_copy_path');

        $this->upload('logo', $this->pngFile(400, 200));
        $secondCopy = $this->storedSetting('logo_email_copy_path');

        $this->assertNotEquals($firstCopy, $secondCopy);
        $this->assertFalse($this->assets()->exists($firstCopy));
        $this->assertTrue($this->assets()->exists($secondCopy));
        $this->assertEquals('240x120', $this->storedSetting('logo_email_copy_size'));
    }

    #[Test]
    public function a_small_logo_is_not_enlarged(): void
    {
        $this->upload('logo', $this->pngFile(90, 30));

        $this->assertEquals('90x30', $this->storedSetting('logo_email_copy_size'));
    }

    #[Test]
    public function a_very_wide_logo_is_capped_in_width(): void
    {
        $this->upload('logo', $this->pngFile(2400, 120));

        $this->assertEquals('1120x56', $this->storedSetting('logo_email_copy_size'));
    }

    #[Test]
    public function an_animated_logo_gets_a_gif_copy(): void
    {
        $frames = [$this->pngFile(400, 100), $this->pngFile(400, 100)];

        $path = tempnam(sys_get_temp_dir(), 'flarum-logo-').'.gif';
        $this->tempFiles[] = $path;

        $this->app()->getContainer()->make(ImageManager::class)
            ->animate(function ($animation) use ($frames) {
                foreach ($frames as $frame) {
                    $animation->add($frame->getStream()->getMetadata('uri'), 0.5);
                }
            })
            ->toGif()
            ->save($path);

        $this->upload('logo', new UploadedFile(new Stream($path), filesize($path), UPLOAD_ERR_OK, 'logo.gif', 'image/gif'));

        $this->assertStringEndsWith('.gif', $this->storedSetting('logo_email_copy_path'));
        $this->assertEquals('400x100', $this->storedSetting('logo_email_copy_size'));
    }

    #[Test]
    public function uploading_the_dark_mode_logo_makes_no_copy(): void
    {
        $response = $this->upload('logo-dark-mode', $this->pngFile(800, 200));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull($this->storedSetting('logo_email_copy_path'));
    }

    #[Test]
    public function deleting_the_logo_deletes_the_copy(): void
    {
        $this->upload('logo', $this->pngFile(800, 200));
        $copyPath = $this->storedSetting('logo_email_copy_path');

        $response = $this->delete('logo');

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull($this->storedSetting('logo_email_copy_path'));
        $this->assertNull($this->storedSetting('logo_email_copy_size'));
        $this->assertFalse($this->assets()->exists($copyPath));
    }

    #[Test]
    public function deleting_the_dark_mode_logo_keeps_the_copy(): void
    {
        $this->upload('logo', $this->pngFile(800, 200));
        $this->upload('logo-dark-mode', $this->pngFile(800, 200));
        $copyPath = $this->storedSetting('logo_email_copy_path');

        $this->delete('logo-dark-mode');

        $this->assertEquals($copyPath, $this->storedSetting('logo_email_copy_path'));
        $this->assertTrue($this->assets()->exists($copyPath));
    }

    #[Test]
    public function an_admin_can_upload_an_email_logo(): void
    {
        $response = $this->upload('email-logo', $this->pngFile(600, 300));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $path = $this->storedSetting('email_logo_path');

        $this->assertStringEndsWith('.png', $path);
        $this->assertTrue($this->assets()->exists($path));
        $this->assertEquals('240x120', $this->storedSetting('email_logo_size'));

        $body = json_decode((string) $response->getBody(), true);
        $this->assertStringEndsWith($path, $body['data']['attributes']['emailLogoUrl']);
    }

    #[Test]
    public function deleting_the_email_logo_keeps_the_copy_of_the_forum_logo(): void
    {
        $this->upload('logo', $this->pngFile(800, 200));
        $this->upload('email-logo', $this->pngFile(600, 300));
        $emailLogoPath = $this->storedSetting('email_logo_path');
        $copyPath = $this->storedSetting('logo_email_copy_path');

        $response = $this->delete('email-logo');

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull($this->storedSetting('email_logo_path'));
        $this->assertNull($this->storedSetting('email_logo_size'));
        $this->assertFalse($this->assets()->exists($emailLogoPath));
        $this->assertEquals($copyPath, $this->storedSetting('logo_email_copy_path'));
        $this->assertTrue($this->assets()->exists($copyPath));
    }

    #[Test]
    public function a_normal_user_cannot_upload_an_email_logo(): void
    {
        $response = $this->upload('email-logo', $this->pngFile(600, 300), 2);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertNull($this->storedSetting('email_logo_path'));
    }

    #[Test]
    public function the_email_logo_url_is_only_shown_to_admins(): void
    {
        $this->upload('email-logo', $this->pngFile(600, 300));

        $asAdmin = json_decode((string) $this->send($this->request('GET', '/api', ['authenticatedAs' => 1]))->getBody(), true);
        $asUser = json_decode((string) $this->send($this->request('GET', '/api', ['authenticatedAs' => 2]))->getBody(), true);

        $this->assertNotEmpty($asAdmin['data']['attributes']['emailLogoUrl'] ?? null);
        $this->assertArrayNotHasKey('emailLogoUrl', $asUser['data']['attributes']);
    }
}
