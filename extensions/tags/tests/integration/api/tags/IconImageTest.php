<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tags\Tests\integration\api\tags;

use Flarum\Tags\Tag;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class IconImageTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private ?string $file = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags');

        $this->prepareDatabase([
            Tag::class => [
                ['id' => 1, 'name' => 'Lions', 'slug' => 'lions', 'icon' => 'fas fa-futbol', 'position' => 0, 'parent_id' => null],
            ],
            User::class => [
                $this->normalUser(),
            ],
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->file) {
            @unlink($this->file);
        }

        // Files on a disk outlive the test's database transaction.
        $this->disk()->delete($this->disk()->allFiles());

        parent::tearDown();
    }

    private function upload(string $tag = '1', int $actorId = 1): ResponseInterface
    {
        $this->file = tempnam(sys_get_temp_dir(), 'flarum-tag-icon-').'.png';

        $image = imagecreatetruecolor(300, 300);
        imagefill($image, 0, 0, imagecolorallocate($image, 0, 128, 0));
        imagepng($image, $this->file);

        return $this->send(
            $this->request('POST', "/api/tags/$tag/icon-image", ['authenticatedAs' => $actorId])
                ->withUploadedFiles(['iconImage' => new UploadedFile(new Stream($this->file), filesize($this->file), UPLOAD_ERR_OK, 'lions.png', 'image/png')])
        );
    }

    private function disk(): Filesystem
    {
        return $this->app()->getContainer()->make('filesystem')->disk('flarum-icons');
    }

    private function storedPath(): ?string
    {
        return $this->database()->table('tags')->where('id', 1)->value('icon_image_path');
    }

    #[Test]
    public function admin_can_upload_an_icon_image(): void
    {
        $response = $this->upload();

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];
        $path = $this->storedPath();

        $this->assertStringEndsWith('/assets/icons/'.$path, $attributes['iconImageUrl']);
        $this->assertStringContainsString('3x', $attributes['iconImageSrcset']);
        $this->assertEquals('fas fa-futbol', $attributes['icon']);
        $this->assertTrue($this->disk()->exists($path));
    }

    #[Test]
    public function a_tag_can_be_addressed_by_its_slug(): void
    {
        $response = $this->upload('lions');

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNotNull($this->storedPath());
    }

    #[Test]
    public function a_user_who_cannot_edit_the_tag_cannot_upload_or_remove_its_image(): void
    {
        $response = $this->upload(actorId: 2);

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull($this->storedPath());

        $response = $this->send(
            $this->request('DELETE', '/api/tags/1/icon-image', ['authenticatedAs' => 2])
        );

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function admin_can_remove_an_icon_image(): void
    {
        $this->upload();
        $path = $this->storedPath();

        $response = $this->send(
            $this->request('DELETE', '/api/tags/1/icon-image', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull(json_decode((string) $response->getBody(), true)['data']['attributes']['iconImageUrl']);
        $this->assertNull($this->storedPath());
        $this->assertFalse($this->disk()->exists($path));
    }

    #[Test]
    public function deleting_a_tag_deletes_its_icon_image(): void
    {
        $this->upload();
        $path = $this->storedPath();

        $response = $this->send(
            $this->request('DELETE', '/api/tags/1', ['authenticatedAs' => 1])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertFalse($this->disk()->exists($path));
        $this->assertFalse($this->disk()->exists(str_replace('.webp', '@3x.webp', $path)));
    }
}
