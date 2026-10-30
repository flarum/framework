<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Flarum\Extend;
use Flarum\Forum\Branding\SplashScreen;
use Flarum\Testing\integration\TestCase;
use Illuminate\Contracts\Filesystem\Filesystem;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class SplashScreenTest extends TestCase
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
     * A red app icon, so it stands out from the background it is drawn on.
     */
    private function redPng(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'flarum-app-icon-').'.png';
        $this->tempFiles[] = $path;

        $image = imagecreatetruecolor(600, 600);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 0, 0));
        imagepng($image, $path);

        return $path;
    }

    /**
     * A full-screen image, red on the left and green on the right, or all
     * blue, for a theme's splash screen.
     */
    private function screenPng(int $width, int $height, bool $blue = false): string
    {
        $path = tempnam(sys_get_temp_dir(), 'flarum-splash-').'.png';
        $this->tempFiles[] = $path;

        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, imagecolorallocate($image, 0, 0, $blue ? 255 : 0));

        if (! $blue) {
            imagefilledrectangle($image, 0, 0, intdiv($width, 2) - 1, $height - 1, imagecolorallocate($image, 255, 0, 0));
            imagefilledrectangle($image, intdiv($width, 2), 0, $width - 1, $height - 1, imagecolorallocate($image, 0, 255, 0));
        }

        imagepng($image, $path);

        return $path;
    }

    private function uploadAppIcon(): void
    {
        $path = $this->redPng();

        $response = $this->send(
            $this->request('POST', '/api/app-icon', ['authenticatedAs' => 1])
                ->withUploadedFiles(['app-icon' => new UploadedFile(new Stream($path), filesize($path), UPLOAD_ERR_OK, 'icon.png', 'image/png')])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    private function saveSettings(array $settings): void
    {
        $response = $this->send($this->request('POST', '/api/settings', [
            'authenticatedAs' => 1,
            'json' => $settings,
        ]));

        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());
    }

    private function assets(): Filesystem
    {
        return $this->app()->getContainer()->make('filesystem')->disk('flarum-assets');
    }

    private function page(): string
    {
        return (string) $this->send($this->request('GET', '/'))->getBody();
    }

    /**
     * @return array<string, string> media query => URL
     */
    private function links(): array
    {
        preg_match_all('/<link rel="apple-touch-startup-image" href="([^"]+)" media="([^"]+)">/', $this->page(), $matches);

        return array_combine($matches[2], $matches[1]);
    }

    private function iPhone16Link(): string
    {
        return $this->links()['(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3) and (orientation: portrait)'];
    }

    /**
     * Where the splash screen an address leads to is stored.
     */
    private function drawnPath(string $url): string
    {
        preg_match('#/splash/(\d+x\d+)\?v=([0-9a-f]{8})$#', $url, $matches);

        return "splash-$matches[2]-$matches[1].png";
    }

    private function fetch(string $url): ResponseInterface
    {
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);

        return $this->send($this->request('GET', $parts['path'])->withQueryParams($query));
    }

    private function image(string $url, string $type = 'image/png'): \GdImage
    {
        $response = $this->fetch($url);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame($type, $response->getHeaderLine('Content-Type'));

        return imagecreatefromstring((string) $response->getBody());
    }

    /**
     * @param array{0: int, 1: int, 2: int} $expected red, green and blue
     */
    private function assertPixel(array $expected, \GdImage $image, int $x, int $y, int $delta = 0): void
    {
        $color = imagecolorat($image, $x, $y);
        $actual = [($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF];

        foreach ($expected as $i => $value) {
            $this->assertEqualsWithDelta($value, $actual[$i], $delta, "The pixel at $x, $y is rgb(".implode(', ', $actual).').');
        }
    }

    #[Test]
    public function there_are_no_splash_screens_without_an_app_icon(): void
    {
        $page = $this->page();

        $this->assertStringNotContainsString('apple-touch-startup-image', $page);
        // The forum still opens as an app.
        $this->assertStringContainsString('<meta name="apple-mobile-web-app-capable" content="yes">', $page);
        $this->assertEquals(404, $this->send($this->request('GET', '/splash/1179x2556'))->getStatusCode());
    }

    #[Test]
    public function an_app_icon_gives_every_iphone_and_ipad_screen_a_splash_screen_in_each_orientation(): void
    {
        $this->uploadAppIcon();

        $links = $this->links();

        $this->assertCount(count(SplashScreen::SCREENS) * 2, $links);
        $this->assertMatchesRegularExpression(
            '#^http://localhost/splash/2556x1179\?v=[0-9a-f]{8}$#',
            $links['(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)']
        );

        $page = $this->page();

        $this->assertStringContainsString('<meta name="apple-mobile-web-app-capable" content="yes">', $page);
        $this->assertStringContainsString('<meta name="mobile-web-app-capable" content="yes">', $page);
    }

    #[Test]
    public function a_splash_screen_is_the_padded_app_icon_in_the_middle_of_its_background(): void
    {
        $this->setting('app_icon_background', '#0000ff');

        $this->uploadAppIcon();

        $image = $this->image($this->iPhone16Link());

        $this->assertSame([1179, 2556], [imagesx($image), imagesy($image)]);

        // As on Android, the maskable icon is drawn 128 points, 384 pixels,
        // across, square, in the middle of the screen. The red fills 80% of
        // it, about 307 pixels, and the rest is the icon's background.
        $this->assertPixel([255, 0, 0], $image, 589, 1278);
        $this->assertPixel([255, 0, 0], $image, 589 - 140, 1278 - 140);
        $this->assertPixel([0, 0, 255], $image, 589 - 165, 1278);
        $this->assertPixel([0, 0, 255], $image, 589, 1278 + 165);
        $this->assertPixel([0, 0, 255], $image, 0, 0);
    }

    #[Test]
    public function a_theme_s_app_icon_is_shown_on_the_background_the_theme_set_it_on(): void
    {
        $this->setting('app_icon_background', '#0000ff');

        $this->extend((new Extend\Theme())->appIcon($this->redPng(), background: '#00ff00'));

        $image = $this->image($this->iPhone16Link());

        $this->assertPixel([255, 0, 0], $image, 589, 1278);
        $this->assertPixel([0, 255, 0], $image, 589 - 200, 1278);
        $this->assertPixel([0, 255, 0], $image, 0, 0);

        $manifest = json_decode((string) $this->send($this->request('GET', '/manifest'))->getBody(), true);

        $this->assertSame('#00ff00', $manifest['background_color']);
    }

    #[Test]
    public function a_theme_s_splash_image_is_cropped_to_cover_each_screen(): void
    {
        $this->extend((new Extend\Theme())->splashScreen($this->screenPng(1320, 2868)));

        $this->assertCount(count(SplashScreen::SCREENS) * 2, $this->links());

        $image = $this->image($this->iPhone16Link(), 'image/jpeg');

        $this->assertSame([1179, 2556], [imagesx($image), imagesy($image)]);
        $this->assertPixel([255, 0, 0], $image, 100, 1278, 12);
        $this->assertPixel([0, 255, 0], $image, 1079, 1278, 12);

        // Held sideways, the screen gets the middle of the image.
        $landscape = $this->image($this->links()['(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)'], 'image/jpeg');

        $this->assertSame([2556, 1179], [imagesx($landscape), imagesy($landscape)]);
        $this->assertPixel([255, 0, 0], $landscape, 100, 589, 12);
        $this->assertPixel([0, 255, 0], $landscape, 2456, 589, 12);
    }

    #[Test]
    public function a_theme_s_landscape_image_is_shown_on_screens_held_sideways(): void
    {
        $this->extend((new Extend\Theme())->splashScreen($this->screenPng(1320, 2868), landscape: $this->screenPng(2868, 1320, blue: true)));

        $links = $this->links();

        $this->assertPixel([255, 0, 0], $this->image($this->iPhone16Link(), 'image/jpeg'), 100, 1278, 12);
        $this->assertPixel([0, 0, 255], $this->image($links['(device-width: 393px) and (device-height: 852px) and (-webkit-device-pixel-ratio: 3) and (orientation: landscape)'], 'image/jpeg'), 1278, 589, 12);
    }

    #[Test]
    public function not_opening_as_an_app_removes_a_theme_s_splash_screen_too(): void
    {
        $this->setting('open_as_app', '0');

        $this->extend((new Extend\Theme())->splashScreen($this->screenPng(1320, 2868)));

        $this->assertSame([], $this->links());
    }

    #[Test]
    public function the_splash_screen_background_can_be_set_apart_from_the_app_icon_s(): void
    {
        $this->setting('app_icon_background', '#0000ff');
        $this->setting('splash_screen_background', '#00ff00');

        $this->extend((new Extend\Theme())->appIcon($this->redPng(), background: '#0000ff'));

        $image = $this->image($this->iPhone16Link());

        $this->assertPixel([255, 0, 0], $image, 589, 1278);
        $this->assertPixel([0, 0, 255], $image, 589 - 165, 1278);
        $this->assertPixel([0, 255, 0], $image, 0, 0);

        $manifest = json_decode((string) $this->send($this->request('GET', '/manifest'))->getBody(), true);

        $this->assertSame('#00ff00', $manifest['background_color']);
    }

    #[Test]
    public function a_landscape_screen_gets_a_landscape_splash_screen(): void
    {
        $this->uploadAppIcon();

        $image = $this->image($this->links()['(device-width: 1032px) and (device-height: 1376px) and (-webkit-device-pixel-ratio: 2) and (orientation: landscape)']);

        $this->assertSame([2752, 2064], [imagesx($image), imagesy($image)]);
    }

    #[Test]
    public function only_iphone_and_ipad_screens_get_splash_screens(): void
    {
        $this->uploadAppIcon();

        $this->assertEquals(404, $this->send($this->request('GET', '/splash/1000x1000'))->getStatusCode());
        $this->assertEquals(404, $this->send($this->request('GET', '/splash/large'))->getStatusCode());
    }

    #[Test]
    public function a_splash_screen_can_be_cached_for_good_at_its_current_address(): void
    {
        $this->uploadAppIcon();

        $this->assertSame('public, max-age=31536000, immutable', $this->fetch($this->iPhone16Link())->getHeaderLine('Cache-Control'));
        $this->assertSame('public, max-age=3600', $this->fetch('/splash/1179x2556?v=00000000')->getHeaderLine('Cache-Control'));
    }

    #[Test]
    public function changing_the_background_draws_the_splash_screens_again(): void
    {
        $this->uploadAppIcon();

        $before = $this->iPhone16Link();
        $this->image($before);
        $this->assertTrue($this->assets()->exists($this->drawnPath($before)));

        $this->saveSettings(['app_icon_background' => '#00ff00']);

        $after = $this->iPhone16Link();

        $this->assertNotSame($before, $after);
        $this->assertFalse($this->assets()->exists($this->drawnPath($before)), 'The splash screens drawn on the old background are kept.');
        $this->assertPixel([0, 255, 0], $this->image($after), 0, 0);
    }

    #[Test]
    public function not_opening_as_an_app_removes_the_splash_screen(): void
    {
        $this->uploadAppIcon();

        $link = $this->iPhone16Link();
        $this->image($link);

        $this->saveSettings(['open_as_app' => false]);

        $this->assertSame([], $this->links());
        $this->assertStringNotContainsString('web-app-capable', $this->page());
        $this->assertFalse($this->assets()->exists($this->drawnPath($link)));
        $this->assertEquals(404, $this->fetch($link)->getStatusCode());
    }

    #[Test]
    public function removing_the_app_icon_removes_the_splash_screens(): void
    {
        $this->uploadAppIcon();

        $link = $this->iPhone16Link();
        $this->image($link);

        $response = $this->send($this->request('DELETE', '/api/app-icon', ['authenticatedAs' => 1]));
        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        $this->assertSame([], $this->links());
        $this->assertFalse($this->assets()->exists($this->drawnPath($link)));
    }
}
