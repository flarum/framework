<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Flarum\Extend;
use Flarum\Frontend\Document;
use Flarum\Testing\integration\TestCase;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\Attributes\Test;

class WebManifestTest extends TestCase
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

    private function uploadAppIcon(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'flarum-app-icon-').'.png';
        $this->tempFiles[] = $path;

        $image = imagecreatetruecolor(600, 600);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 0, 0));
        imagepng($image, $path);

        $response = $this->send(
            $this->request('POST', '/api/app-icon', ['authenticatedAs' => 1])
                ->withUploadedFiles(['app-icon' => new UploadedFile(new Stream($path), filesize($path), UPLOAD_ERR_OK, 'icon.png', 'image/png')])
        );

        $this->assertEquals(200, $response->getStatusCode(), (string) $response->getBody());
    }

    private function appIconUrl(string $name): string
    {
        $paths = json_decode((string) $this->database()->table('settings')->where('key', 'app_icon_paths')->value('value'), true);

        return $this->app()->getContainer()->make('filesystem')->disk('flarum-assets')->url($paths[$name]);
    }

    private function manifest(): array
    {
        $response = $this->send($this->request('GET', '/manifest'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('application/manifest+json', $response->getHeaderLine('Content-Type'));

        return json_decode((string) $response->getBody(), true);
    }

    #[Test]
    public function the_manifest_lists_the_home_screen_icons(): void
    {
        $this->uploadAppIcon();

        $manifest = $this->manifest();

        $this->assertSame([
            ['src' => $this->appIconUrl('icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => $this->appIconUrl('icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png'],
            ['src' => $this->appIconUrl('icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ], $manifest['icons']);
    }

    #[Test]
    public function the_manifest_opens_the_forum_in_the_browser(): void
    {
        $manifest = $this->manifest();

        $this->assertSame('browser', $manifest['display']);
        $this->assertSame('http://localhost/', $manifest['start_url']);
        $this->assertSame('http://localhost/', $manifest['scope']);
    }

    #[Test]
    public function the_page_links_the_manifest_once_there_is_an_app_icon(): void
    {
        $this->assertStringNotContainsString('rel="manifest"', (string) $this->send($this->request('GET', '/'))->getBody());

        $this->uploadAppIcon();

        $this->assertStringContainsString('<link rel="manifest" href="http://localhost/manifest">', (string) $this->send($this->request('GET', '/'))->getBody());
    }

    #[Test]
    public function the_manifest_stays_linked_when_an_extension_adds_its_own_tags(): void
    {
        $this->extend(
            (new Extend\Frontend('forum'))->content(function (Document $document) {
                $document->head[] = "<link rel='manifest' href='/webmanifest'>";
            })
        );

        $this->uploadAppIcon();

        $this->assertStringContainsString('<link rel="manifest" href="http://localhost/manifest">', (string) $this->send($this->request('GET', '/'))->getBody());
    }

    #[Test]
    public function an_extension_that_changes_the_manifest_gets_it_linked_without_an_app_icon(): void
    {
        $this->extend(
            (new Extend\Theme())->manifest(fn (array $manifest) => ['display' => 'standalone'] + $manifest)
        );

        $this->assertStringContainsString('<link rel="manifest" href="http://localhost/manifest">', (string) $this->send($this->request('GET', '/'))->getBody());
    }

    #[Test]
    public function an_extension_can_change_the_manifest(): void
    {
        $this->extend(
            (new Extend\Theme())->manifest(fn (array $manifest) => ['display' => 'standalone'] + $manifest)
        );

        $this->assertSame('standalone', $this->manifest()['display']);
    }

    #[Test]
    public function favicon_ico_at_the_forum_root_redirects_to_the_icon(): void
    {
        $this->uploadAppIcon();

        $response = $this->send($this->request('GET', '/favicon.ico'));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertSame($this->appIconUrl('favicon.ico'), $response->getHeaderLine('Location'));
    }

    #[Test]
    public function favicon_ico_is_not_found_without_an_icon(): void
    {
        $this->assertEquals(404, $this->send($this->request('GET', '/favicon.ico'))->getStatusCode());
    }
}
