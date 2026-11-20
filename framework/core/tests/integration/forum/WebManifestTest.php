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
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;

class WebManifestTest extends TestCase
{
    use RetrievesAuthorizedUsers;

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
    public function the_manifest_opens_the_forum_as_an_app(): void
    {
        $manifest = $this->manifest();

        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame('http://localhost/', $manifest['start_url']);
        $this->assertSame('http://localhost/', $manifest['scope']);
        $this->assertArrayNotHasKey('id', $manifest);
    }

    private function saveStartPage(string $page, int $actor = 1): ResponseInterface
    {
        return $this->send($this->request('POST', '/api/settings', ['authenticatedAs' => $actor, 'json' => ['app_start_page' => $page]]));
    }

    private function registerExtensionPage(): void
    {
        $this->extend((new Extend\Frontend('forum'))->route('/start-here', 'acme.start-here'));
    }

    #[Test]
    #[DataProvider('startPages')]
    public function the_manifest_opens_the_start_page_and_keeps_the_app_s_id(string $page, bool $fromExtension, string $startUrl): void
    {
        if ($fromExtension) {
            $this->registerExtensionPage();
        }

        $response = $this->saveStartPage($page);
        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        $manifest = $this->manifest();

        $this->assertSame($startUrl, $manifest['start_url']);
        $this->assertSame('http://localhost/', $manifest['id']);
        $this->assertSame('http://localhost/', $manifest['scope']);
    }

    public static function startPages(): array
    {
        return [
            'a core page' => ['/all', false, 'http://localhost/all'],
            'an extension\'s page' => ['/start-here', true, 'http://localhost/start-here'],
        ];
    }

    #[Test]
    #[DataProvider('refusedStartPages')]
    public function a_start_page_that_is_not_a_forum_page_cannot_be_saved(string $page): void
    {
        $response = $this->saveStartPage($page);

        $this->assertEquals(422, $response->getStatusCode(), (string) $response->getBody());
        $this->assertSame('The selected app start page is invalid.', json_decode((string) $response->getBody(), true)['errors'][0]['detail']);
        $this->assertNull($this->database()->table('settings')->where('key', 'app_start_page')->value('value'));
    }

    public static function refusedStartPages(): array
    {
        return [
            'an unknown path' => ['/nowhere'],
            'a path without its slash' => ['all'],
            'a full URL' => ['http://localhost/all'],
            'a page that takes parameters' => ['/d/1'],
            'a page that takes parameters, as its pattern' => ['/d/{id:\d+(?:-[^/]*)?}[/{near:[^/]*}]'],
            'a route that is not a page' => ['/login'],
        ];
    }

    #[Test]
    public function an_empty_start_page_opens_the_home_page(): void
    {
        $response = $this->saveStartPage('');
        $this->assertEquals(204, $response->getStatusCode(), (string) $response->getBody());

        $manifest = $this->manifest();

        $this->assertSame('http://localhost/', $manifest['start_url']);
        $this->assertArrayNotHasKey('id', $manifest);
    }

    #[Test]
    public function a_start_page_whose_extension_is_gone_falls_back_to_the_home_page(): void
    {
        $this->setting('app_start_page', '/start-here');

        $manifest = $this->manifest();

        $this->assertSame('http://localhost/', $manifest['start_url']);
        $this->assertArrayNotHasKey('id', $manifest);
    }

    #[Test]
    public function only_admins_can_choose_the_start_page(): void
    {
        $this->prepareDatabase(['users' => [$this->normalUser()]]);

        $response = $this->saveStartPage('/all', 2);

        $this->assertEquals(403, $response->getStatusCode(), (string) $response->getBody());
        $this->assertNull($this->database()->table('settings')->where('key', 'app_start_page')->value('value'));
    }

    #[Test]
    public function the_manifest_opens_the_forum_in_the_browser_when_it_isn_t_to_open_as_an_app(): void
    {
        $this->setting('open_as_app', '0');

        $this->uploadAppIcon();

        $this->assertSame('browser', $this->manifest()['display']);
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

    #[Test]
    public function shared_caches_may_not_store_the_manifest_with_a_session_cookie(): void
    {
        // A cache that stored it would hand one visitor's session to others.
        $response = $this->send($this->request('GET', '/manifest'));

        $this->assertNotEmpty($response->getHeaderLine('Set-Cookie'));
        $this->assertStringContainsString('private', $response->getHeaderLine('Cache-Control'));
    }

    #[Test]
    public function shared_caches_may_not_store_the_favicon_redirect_with_a_session_cookie(): void
    {
        $this->uploadAppIcon();

        $response = $this->send($this->request('GET', '/favicon.ico'));

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertNotEmpty($response->getHeaderLine('Set-Cookie'));
        $this->assertStringContainsString('private', $response->getHeaderLine('Cache-Control'));
    }
}
