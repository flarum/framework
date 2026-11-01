<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\api\groups;

use Flarum\Extend;
use Flarum\Foundation\IconImage\Event\Saving;
use Flarum\Group\Group;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Exception\PermissionDeniedException;
use Flarum\User\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Intervention\Image\ImageManager;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class IconImageTest extends TestCase
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
            Group::class => [
                ['id' => 10, 'name_singular' => 'Fan', 'name_plural' => 'Fans', 'icon' => 'fas fa-futbol'],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }

        // Files on a disk outlive the test's database transaction.
        $this->disk()->delete($this->disk()->allFiles());

        parent::tearDown();
    }

    private function pngFile(int $width, int $height): string
    {
        $path = tempnam(sys_get_temp_dir(), 'flarum-icon-image-').'.png';
        $this->tempFiles[] = $path;

        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 0, 0));
        imagepng($image, $path);

        return $path;
    }

    private function animatedGifFile(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'flarum-icon-image-').'.gif';
        $this->tempFiles[] = $path;

        $manager = $this->app()->getContainer()->make(ImageManager::class);

        $gif = $manager->animate(function ($animation) use ($manager) {
            $animation->add($manager->create(200, 200)->fill('ff0000'), .25);
            $animation->add($manager->create(200, 200)->fill('0000ff'), .25);
        })->toGif();

        file_put_contents($path, (string) $gif);

        return $path;
    }

    private function upload(string $path, string $name = 'icon.png', string $type = 'image/png', int $actorId = 1, int $groupId = 10): ResponseInterface
    {
        return $this->send(
            $this->request('POST', "/api/groups/$groupId/icon-image", ['authenticatedAs' => $actorId])
                ->withUploadedFiles(['iconImage' => new UploadedFile(new Stream($path), filesize($path), UPLOAD_ERR_OK, $name, $type)])
        );
    }

    private function disk(): Filesystem
    {
        return $this->app()->getContainer()->make('filesystem')->disk('flarum-icons');
    }

    private function storedPath(int $groupId = 10): ?string
    {
        return $this->database()->table('groups')->where('id', $groupId)->value('icon_image_path');
    }

    private function variant(string $path, string $suffix): string
    {
        return str_replace('.webp', $suffix.'.webp', $path);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function dimensions(string $path): array
    {
        $size = getimagesizefromstring($this->disk()->get($path));

        return [$size[0], $size[1]];
    }

    #[Test]
    public function admin_can_upload_an_icon_image_and_every_scale_is_stored(): void
    {
        $response = $this->upload($this->pngFile(400, 200));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];
        $path = $this->storedPath();

        $this->assertStringEndsWith('.webp', $path);
        $this->assertStringEndsWith('/assets/icons/'.$path, $attributes['iconImageUrl']);
        $this->assertStringContainsString($path.' 1x', $attributes['iconImageSrcset']);
        $this->assertStringContainsString($this->variant($path, '@2x').' 2x', $attributes['iconImageSrcset']);
        $this->assertStringContainsString($this->variant($path, '@3x').' 3x', $attributes['iconImageSrcset']);

        // Scaled to fit the box, keeping the aspect ratio rather than cropping.
        $this->assertEquals([64, 32], $this->dimensions($path));
        $this->assertEquals([128, 64], $this->dimensions($this->variant($path, '@2x')));
        $this->assertEquals([192, 96], $this->dimensions($this->variant($path, '@3x')));

        // The Font Awesome icon is kept, for whatever can't show the image.
        $this->assertEquals('fas fa-futbol', $attributes['icon']);
    }

    #[Test]
    public function a_small_image_is_not_upscaled_into_hidpi_variants(): void
    {
        $response = $this->upload($this->pngFile(150, 150));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];
        $path = $this->storedPath();

        $this->assertEquals(2, $this->database()->table('groups')->where('id', 10)->value('icon_image_scales'));
        $this->assertStringContainsString('2x', $attributes['iconImageSrcset']);
        $this->assertStringNotContainsString('3x', $attributes['iconImageSrcset']);
        $this->assertTrue($this->disk()->exists($this->variant($path, '@2x')));
        $this->assertFalse($this->disk()->exists($this->variant($path, '@3x')));
    }

    #[Test]
    public function an_image_smaller_than_the_box_has_no_srcset(): void
    {
        $response = $this->upload($this->pngFile(40, 40));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];

        $this->assertNotNull($attributes['iconImageUrl']);
        $this->assertNull($attributes['iconImageSrcset']);
        $this->assertEquals([40, 40], $this->dimensions($this->storedPath()));
    }

    #[Test]
    public function an_animated_image_keeps_only_its_first_frame(): void
    {
        $response = $this->upload($this->animatedGifFile(), 'icon.gif', 'image/gif');

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $stored = $this->disk()->get($this->storedPath());

        // An animated WebP has an ANIM chunk.
        $this->assertStringNotContainsString('ANIM', $stored);

        $image = imagecreatefromstring($stored);
        $color = imagecolorat($image, 32, 32);
        $this->assertGreaterThan(200, ($color >> 16) & 0xFF, 'Expected the first (red) frame.');
    }

    #[Test]
    public function replacing_an_image_deletes_the_previous_files(): void
    {
        $this->upload($this->pngFile(400, 400));
        $first = $this->storedPath();

        $response = $this->upload($this->pngFile(400, 400));

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNotEquals($first, $this->storedPath());
        $this->assertFalse($this->disk()->exists($first));
        $this->assertFalse($this->disk()->exists($this->variant($first, '@2x')));
        $this->assertFalse($this->disk()->exists($this->variant($first, '@3x')));
        $this->assertTrue($this->disk()->exists($this->storedPath()));
    }

    #[Test]
    public function admin_can_remove_an_icon_image(): void
    {
        $this->upload($this->pngFile(400, 400));
        $path = $this->storedPath();

        $response = $this->send(
            $this->request('DELETE', '/api/groups/10/icon-image', ['authenticatedAs' => 1])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];

        $this->assertNull($attributes['iconImageUrl']);
        $this->assertNull($attributes['iconImageSrcset']);
        $this->assertNull($this->storedPath());
        $this->assertFalse($this->disk()->exists($path));
        $this->assertFalse($this->disk()->exists($this->variant($path, '@3x')));
    }

    #[Test]
    public function deleting_a_group_deletes_its_icon_image(): void
    {
        $this->upload($this->pngFile(400, 400));
        $path = $this->storedPath();

        $response = $this->send(
            $this->request('DELETE', '/api/groups/10', ['authenticatedAs' => 1])
        );

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertFalse($this->disk()->exists($path));
        $this->assertFalse($this->disk()->exists($this->variant($path, '@2x')));
    }

    #[Test]
    public function a_user_who_cannot_edit_the_group_cannot_upload_or_remove_its_image(): void
    {
        $response = $this->upload($this->pngFile(100, 100), actorId: 2);

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull($this->storedPath());

        $response = $this->send(
            $this->request('DELETE', '/api/groups/10/icon-image', ['authenticatedAs' => 2])
        );

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
    }

    #[Test]
    public function an_svg_is_rejected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'flarum-icon-image-').'.svg';
        $this->tempFiles[] = $path;
        file_put_contents($path, '<svg xmlns="http://www.w3.org/2000/svg" width="10" height="10"><script>alert(1)</script></svg>');

        $response = $this->upload($path, 'icon.svg', 'image/svg+xml');

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull($this->storedPath());
    }

    #[Test]
    public function a_file_that_is_not_an_image_is_rejected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'flarum-icon-image-').'.png';
        $this->tempFiles[] = $path;
        file_put_contents($path, 'not an image');

        $response = $this->upload($path);

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull($this->storedPath());
        $this->assertEmpty($this->disk()->allFiles());
    }

    #[Test]
    public function a_saving_listener_can_stop_the_change(): void
    {
        $this->extend(
            (new Extend\Event())->listen(Saving::class, function (Saving $event) {
                throw new PermissionDeniedException();
            })
        );

        $response = $this->upload($this->pngFile(400, 400));

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull($this->storedPath());
        $this->assertEmpty($this->disk()->allFiles());
    }

    #[Test]
    public function the_saving_event_carries_the_image_or_null_on_removal(): void
    {
        $events = [];

        $this->extend(
            (new Extend\Event())->listen(Saving::class, function (Saving $event) use (&$events) {
                $events[] = [$event->model->id, $event->actor->id, $event->image?->width()];
            })
        );

        $this->upload($this->pngFile(400, 200));
        $this->send($this->request('DELETE', '/api/groups/10/icon-image', ['authenticatedAs' => 1]));

        $this->assertEquals([[10, 1, 400], [10, 1, null]], $events);
    }

    #[Test]
    public function the_new_files_are_deleted_when_the_model_fails_to_save(): void
    {
        $this->app();

        Group::saving(function () {
            throw new RuntimeException('Saving failed.');
        });

        $response = $this->upload($this->pngFile(400, 400));

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertNull($this->storedPath());
        $this->assertEmpty($this->disk()->allFiles());
    }

    #[Test]
    public function a_request_without_a_file_is_rejected(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/groups/10/icon-image', ['authenticatedAs' => 1])
        );

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());

        $errors = json_decode((string) $response->getBody(), true)['errors'];
        $this->assertEquals('/data/attributes/iconImage', $errors[0]['source']['pointer']);
    }

    #[Test]
    public function a_group_without_an_image_serializes_null_image_fields(): void
    {
        $response = $this->send(
            $this->request('GET', '/api/groups/10', ['authenticatedAs' => 2])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());

        $attributes = json_decode((string) $response->getBody(), true)['data']['attributes'];

        $this->assertArrayHasKey('iconImageUrl', $attributes);
        $this->assertNull($attributes['iconImageUrl']);
        $this->assertNull($attributes['iconImageSrcset']);
    }
}
