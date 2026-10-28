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
        $this->assertSame('application/opensearchdescription+xml; charset=utf-8', $response->getHeaderLine('Content-Type'));

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
        $this->assertSame('http://localhost/?q={searchTerms}', $urls['text/html']['template']);
        $this->assertSame('http://localhost/api/opensearch/suggestions?q={searchTerms}', $urls['application/x-suggestions+json']['template']);
        $this->assertSame(['type' => 'application/opensearchdescription+xml', 'rel' => 'self', 'template' => 'http://localhost/opensearch'], $urls['application/opensearchdescription+xml']);
        $this->assertSame('http://localhost/', (string) $xml->children('http://www.mozilla.org/2006/browser/search/')->SearchForm);
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
}
