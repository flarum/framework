<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Laminas\Diactoros\Stream;
use Laminas\Diactoros\UploadedFile;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;

class OpenSearchTest extends TestCase
{
    use RetrievesAuthorizedUsers;

    private const NS = 'http://a9.com/-/spec/opensearch/1.1/';

    protected function setUp(): void
    {
        parent::setUp();

        $this->setting('forum_title', 'Acme Forum');
        $this->setting('forum_description', 'Talk about <b>rockets</b> & more.');
    }

    private function description(): SimpleXMLElement
    {
        $response = $this->send($this->request('GET', '/opensearch'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('application/xml; charset=utf-8', $response->getHeaderLine('Content-Type'));

        $xml = simplexml_load_string((string) $response->getBody());

        $this->assertNotFalse($xml, 'The description is not well-formed XML.');
        $this->assertSame(self::NS, $xml->getNamespaces()['']);

        return $xml;
    }

    /**
     * @return array<string, array<string, string>> type => attributes
     */
    private function urls(SimpleXMLElement $xml): array
    {
        $urls = [];

        foreach ($xml->Url as $url) {
            $urls[(string) $url['type']] = array_map('strval', iterator_to_array($url->attributes()));
        }

        return $urls;
    }

    private function html(): string
    {
        return (string) $this->send($this->request('GET', '/'))->getBody();
    }

    #[Test]
    public function the_description_tells_browsers_how_to_search_the_forum(): void
    {
        $xml = $this->description();
        $urls = $this->urls($xml);

        $this->assertSame('Acme Forum', (string) $xml->ShortName);
        $this->assertSame('Talk about rockets & more.', (string) $xml->Description);
        $this->assertSame('UTF-8', (string) $xml->InputEncoding);
        $this->assertSame('http://localhost/opensearch/search?q={searchTerms}', $urls['text/html']['template']);
        $this->assertSame('http://localhost/api/opensearch/suggestions?q={searchTerms}', $urls['application/x-suggestions+json']['template']);
        $this->assertSame(['type' => 'application/opensearchdescription+xml', 'rel' => 'self', 'template' => 'http://localhost/opensearch'], $urls['application/opensearchdescription+xml']);
        $this->assertSame('http://localhost/', (string) $xml->children('http://www.mozilla.org/2006/browser/search/')->SearchForm);
        $this->assertSame('flarum discussion forum', (string) $xml->Tags);
        $this->assertSame(['role' => 'example', 'searchTerms' => 'Acme Forum'], array_map('strval', iterator_to_array($xml->Query->attributes())));
    }

    private function search(array $query): ResponseInterface
    {
        return $this->send($this->request('GET', '/opensearch/search')->withQueryParams($query));
    }

    #[Test]
    public function a_search_goes_to_the_discussion_list_at_the_root_when_it_is_the_homepage(): void
    {
        $response = $this->search(['q' => 'hello world']);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertSame('http://localhost/?q=hello%20world', $response->getHeaderLine('Location'));
    }

    #[Test]
    public function a_search_goes_to_the_discussion_list_on_a_forum_with_another_homepage(): void
    {
        // The homepage would ignore the query; the discussion list is at /all.
        // Browsers keep the address they were given, so it can't depend on this.
        $this->setting('default_route', '/tags');

        $this->assertSame('http://localhost/all?q=hello%20world', $this->search(['q' => 'hello world'])->getHeaderLine('Location'));
        $this->assertSame('http://localhost/opensearch/search?q={searchTerms}', $this->urls($this->description())['text/html']['template']);
    }

    #[Test]
    public function a_search_for_nothing_goes_to_the_discussion_list(): void
    {
        $this->setting('default_route', '/tags');

        $this->assertSame('http://localhost/all', $this->search(['q' => ' '])->getHeaderLine('Location'));
        $this->assertSame('http://localhost/all', $this->search(['q' => ['x']])->getHeaderLine('Location'));
    }

    #[Test]
    public function every_page_links_the_description(): void
    {
        $this->assertStringContainsString(
            '<link rel="search" type="application/opensearchdescription+xml" title="Acme Forum" href="http://localhost/opensearch">',
            $this->html()
        );
    }

    #[Test]
    public function a_long_title_is_cut_at_the_last_whole_word_that_fits(): void
    {
        $this->setting('forum_title', 'The Acme Rocket Builders Forum');

        $this->assertSame('The Acme Rocket', (string) $this->description()->ShortName);
        $this->assertStringContainsString('title="The Acme Rocket"', $this->html());
    }

    #[Test]
    public function the_short_name_is_used_when_set(): void
    {
        $this->setting('forum_short_name', 'Rockets');

        $this->assertSame('Rockets', (string) $this->description()->ShortName);
        $this->assertStringContainsString('title="Rockets"', $this->html());
        $this->assertSame('Rockets', json_decode((string) $this->send($this->request('GET', '/manifest'))->getBody(), true)['short_name']);
    }

    #[Test]
    public function characters_that_mean_something_in_xml_are_escaped(): void
    {
        $this->setting('forum_title', 'R&D <Lab>');

        $this->assertSame('R&D <Lab>', (string) $this->description()->ShortName);
    }

    #[Test]
    public function the_favicon_is_embedded_in_the_description(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'flarum-favicon-').'.png';
        $image = imagecreatetruecolor(128, 128);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 30, 60));
        imagepng($image, $path);

        $this->send(
            $this->request('POST', '/api/favicon', ['authenticatedAs' => 1])
                ->withUploadedFiles(['favicon' => new UploadedFile(new Stream($path), filesize($path), UPLOAD_ERR_OK, 'favicon.png', 'image/png')])
        );
        @unlink($path);

        $images = [];

        foreach ($this->description()->Image as $element) {
            $images[(string) $element['type']] = [(int) $element['width'], (int) $element['height'], (string) $element];
        }

        $this->assertSame([16, 16], array_slice($images['image/x-icon'], 0, 2));
        $this->assertStringStartsWith('data:image/x-icon;base64,', $images['image/x-icon'][2]);
        $this->assertSame([64, 64], array_slice($images['image/png'], 0, 2));
        $this->assertSame([64, 64, IMAGETYPE_PNG], array_slice(getimagesizefromstring(base64_decode(substr($images['image/png'][2], strlen('data:image/png;base64,')))), 0, 3));
    }

    #[Test]
    public function a_large_favicon_stored_before_is_embedded_at_64px(): void
    {
        $this->setting('favicon_path', 'favicon-old.png');

        $image = imagecreatetruecolor(192, 192);
        ob_start();
        imagepng($image);
        $this->app()->getContainer()->make('filesystem')->disk('flarum-assets')->put('favicon-old.png', (string) ob_get_clean());

        foreach ($this->description()->Image as $element) {
            if ((string) $element['type'] === 'image/png') {
                $this->assertSame([64, 64], [(int) $element['width'], (int) $element['height']]);
                $this->assertSame([64, 64], array_slice(getimagesizefromstring(base64_decode(substr((string) $element, strlen('data:image/png;base64,')))), 0, 2));

                return;
            }
        }

        $this->fail('The favicon is not embedded as a PNG.');
    }

    #[Test]
    public function a_short_name_longer_than_16_characters_is_refused(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/settings', [
                'authenticatedAs' => 1,
                'json' => ['forum_short_name' => 'Seventeen chars!!'],
            ])
        );

        $this->assertEquals(422, $response->getStatusCode());
    }

    #[Test]
    public function control_characters_pasted_into_settings_are_left_out(): void
    {
        // XML can't contain them at all, so browsers would refuse the document.
        $this->setting('forum_title', "Acme\x01 Forum");
        $this->setting('forum_description', "Rockets\x0B and more.");

        $xml = $this->description();

        $this->assertSame('Acme Forum', (string) $xml->ShortName);
        $this->assertSame('Rockets and more.', (string) $xml->Description);
    }

    /**
     * An .ico of PNGs at the given sizes, filled with noise so they don't compress.
     */
    private function ico(array $sizes): string
    {
        mt_srand(1);
        $pngs = [];

        foreach ($sizes as $size) {
            $image = imagecreatetruecolor($size, $size);

            for ($y = 0; $y < $size; $y++) {
                for ($x = 0; $x < $size; $x++) {
                    imagesetpixel($image, $x, $y, mt_rand(0, 0xFFFFFF));
                }
            }

            ob_start();
            imagepng($image);
            $pngs[$size] = (string) ob_get_clean();
        }

        $directory = $data = '';
        $offset = 6 + 16 * count($pngs);

        foreach ($pngs as $size => $png) {
            $directory .= pack('CCCCvvVV', $size % 256, $size % 256, 0, 0, 1, 32, strlen($png), $offset + strlen($data));
            $data .= $png;
        }

        return pack('vvv', 0, 1, count($pngs)).$directory.$data;
    }

    #[Test]
    public function a_large_ico_favicon_does_not_make_the_description_too_big_for_chrome(): void
    {
        // Chrome ignores a description over 50,000 bytes, and with it the forum.
        $this->setting('favicon_path', 'favicon-big.ico');
        $this->app()->getContainer()->make('filesystem')->disk('flarum-assets')->put('favicon-big.ico', $this->ico([16, 32, 48, 128]));

        $response = $this->send($this->request('GET', '/opensearch'));

        $this->assertLessThan(50000, strlen((string) $response->getBody()));
        $this->assertNotFalse(simplexml_load_string((string) $response->getBody()));
    }

    #[Test]
    public function an_ico_favicon_is_embedded_with_its_size(): void
    {
        $this->setting('favicon_path', 'favicon-small.ico');
        $this->app()->getContainer()->make('filesystem')->disk('flarum-assets')->put('favicon-small.ico', $this->ico([32]));

        $image = $this->description()->Image;

        $this->assertSame(['32', '32', 'image/x-icon'], [(string) $image['width'], (string) $image['height'], (string) $image['type']]);
    }

    #[Test]
    public function shared_caches_may_not_store_the_description_with_a_session_cookie(): void
    {
        // A cache that stored it would hand one visitor's session to others.
        $response = $this->send($this->request('GET', '/opensearch'));

        $this->assertNotEmpty($response->getHeaderLine('Set-Cookie'));
        $this->assertStringContainsString('private', $response->getHeaderLine('Cache-Control'));
    }
}
