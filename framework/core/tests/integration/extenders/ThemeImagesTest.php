<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\extenders;

use Flarum\Extend;
use Flarum\Forum\Branding\Branding;
use Flarum\Mail\EmailLogo;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Filesystem\Filesystem;
use InvalidArgumentException;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\UploadedFile;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\Test;

class ThemeImagesTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 10 10"><rect width="10" height="10" fill="red"/></svg>';

    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir().'/flarum-theme-images-'.bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir.'/*'));
        rmdir($this->dir);

        parent::tearDown();
    }

    private function png(string $name, int $width, int $height, array $rgb = [255, 0, 0]): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, ...$rgb));
        imagepng($image, $path = "$this->dir/$name");

        return $path;
    }

    private function svg(string $name): string
    {
        file_put_contents($path = "$this->dir/$name", self::SVG);

        return $path;
    }

    private function assets(): Filesystem
    {
        return $this->app()->getContainer()->make('filesystem')->disk('flarum-assets');
    }

    private function forum(): array
    {
        $response = $this->send($this->request('GET', '/api', ['authenticatedAs' => 1]));

        return json_decode((string) $response->getBody(), true)['data']['attributes'];
    }

    private function stored(string $url): string
    {
        return $this->assets()->get(basename(parse_url($url, PHP_URL_PATH)));
    }

    private function html(): string
    {
        return (string) $this->send($this->request('GET', '/'))->getBody();
    }

    #[Test]
    public function a_logo_set_by_a_theme_is_stored_like_an_uploaded_one(): void
    {
        $this->extend((new Extend\Theme())->logo($this->png('logo.png', 800, 200)));

        $logo = $this->stored($this->forum()['logoUrl']);

        $this->assertEquals([360, 90, IMAGETYPE_WEBP], array_slice(getimagesizefromstring($logo), 0, 3));
    }

    #[Test]
    public function an_svg_logo_set_by_a_theme_is_used_as_it_is(): void
    {
        $this->extend((new Extend\Theme())->logo($this->svg('logo.svg'))->logoDarkMode($this->svg('logo-dark.svg')));

        $forum = $this->forum();

        $this->assertSame(self::SVG, $this->stored($forum['logoUrl']));
        $this->assertSame(self::SVG, $this->stored($forum['logoDarkModeUrl']));
    }

    #[Test]
    public function a_favicon_and_app_icon_set_by_a_theme_are_linked_in_the_page(): void
    {
        $this->extend(
            (new Extend\Theme())
                ->favicon($this->png('favicon.png', 64, 64), svg: $this->svg('favicon.svg'))
                ->appIcon($this->png('icon.png', 512, 512), background: '#123456')
        );

        $html = $this->html();

        $this->assertMatchesRegularExpression('#<link rel="icon" href="[^"]+\.ico" sizes="32x32">#', $html);
        $this->assertMatchesRegularExpression('#<link rel="icon" href="[^"]+\.svg" type="image/svg\+xml">#', $html);
        $this->assertMatchesRegularExpression('#<link rel="apple-touch-icon" href="[^"]+\.png">#', $html);
    }

    #[Test]
    public function the_app_icon_background_a_theme_sets_is_used(): void
    {
        $this->extend((new Extend\Theme())->appIcon($this->png('icon.png', 512, 400), background: '#123456'));

        $apple = imagecreatefromstring($this->stored($this->forum()['appleTouchIconUrl']));

        $this->assertSame(0x123456, imagecolorat($apple, 90, 2) & 0xFFFFFF);
    }

    #[Test]
    public function an_image_a_theme_sets_cannot_be_uploaded_or_removed(): void
    {
        $this->extend((new Extend\Theme())->logo($this->svg('logo.svg')));

        $file = $this->png('upload.png', 400, 100);

        $upload = $this->send(
            $this->request('POST', '/api/logo', ['authenticatedAs' => 1])
                ->withUploadedFiles(['logo' => new UploadedFile(new Stream($file), filesize($file), UPLOAD_ERR_OK, 'logo.png', 'image/png')])
        );
        $remove = $this->send($this->request('DELETE', '/api/logo', ['authenticatedAs' => 1]));

        $this->assertEquals(422, $upload->getStatusCode());
        $this->assertEquals(422, $remove->getStatusCode());
    }

    #[Test]
    public function the_admin_page_knows_which_images_a_theme_sets(): void
    {
        $this->extend((new Extend\Theme())->logo($this->svg('logo.svg'))->appIcon($this->png('icon.png', 512, 512)));

        $html = (string) $this->send($this->request('GET', '/admin', ['authenticatedAs' => 1]))->getBody();

        preg_match('#"themeImages":(\{[^}]*\})#', $html, $matches);

        $this->assertSame(['logo', 'app_icon'], array_keys(json_decode($matches[1] ?? '{}', true)));
    }

    /**
     * Each request resolves its own, as a forum's requests do.
     */
    private function logoUrl(): ?string
    {
        return $this->app()->getContainer()->make(Branding::class)->logoUrl();
    }

    #[Test]
    public function a_changed_theme_image_is_drawn_again(): void
    {
        $file = $this->png('logo.png', 800, 200);

        $this->extend((new Extend\Theme())->logo($file));

        $first = $this->logoUrl();

        $this->png('logo.png', 400, 200, [0, 0, 255]);
        touch($file, time() + 5);
        clearstatcache();

        $second = $this->logoUrl();

        $this->assertNotSame($first, $second);
        $this->assertEquals([180, 90], array_slice(getimagesizefromstring($this->stored($second)), 0, 2));
        $this->assertFalse($this->assets()->exists(basename(parse_url($first, PHP_URL_PATH))));
    }

    #[Test]
    public function an_unchanged_theme_image_is_not_drawn_again(): void
    {
        $this->extend((new Extend\Theme())->logo($this->png('logo.png', 800, 200)));

        $path = basename(parse_url($this->logoUrl(), PHP_URL_PATH));
        $this->assets()->delete($path);

        $this->logoUrl();

        $this->assertFalse($this->assets()->exists($path));
    }

    #[Test]
    public function an_image_that_cannot_be_drawn_is_logged_once_and_left_out(): void
    {
        file_put_contents($broken = "$this->dir/broken.png", 'not an image');

        $this->extend((new Extend\Theme())->logo($broken));

        $log = new TestHandler();
        $this->app()->getContainer()->instance('log', new Logger('test', [$log]));

        $this->assertEquals(200, $this->send($this->request('GET', '/'))->getStatusCode());
        $this->assertNull($this->logoUrl());
        $this->assertCount(1, $log->getRecords());
    }

    #[Test]
    public function emails_use_a_copy_of_a_theme_logo(): void
    {
        $this->extend((new Extend\Theme())->logo($this->png('logo.png', 800, 200)));

        $this->forum();

        $logo = $this->app()->getContainer()->make(EmailLogo::class)->resolve();

        $this->assertSame(IMAGETYPE_PNG, getimagesizefromstring($this->stored($logo['url']))[2]);
        $this->assertSame([240, 60], [$logo['width'], $logo['height']]);
    }

    #[Test]
    public function emails_use_the_email_logo_a_theme_sets(): void
    {
        $this->extend(
            (new Extend\Theme())
                ->logo($this->svg('logo.svg'))
                ->emailLogo($this->png('email.png', 300, 100))
        );

        $this->forum();

        $logo = $this->app()->getContainer()->make(EmailLogo::class)->resolve();

        $this->assertSame([180, 60], [$logo['width'], $logo['height']]);
    }

    #[Test]
    public function an_app_icon_cannot_be_an_svg(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Extend\Theme())->appIcon($this->svg('icon.svg'));
    }

    #[Test]
    public function a_missing_file_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new Extend\Theme())->logo($this->dir.'/missing.png');
    }
}
