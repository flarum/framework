<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Tests\integration\forum;

use Flarum\Http\UrlGenerator;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ServiceWorkerTest extends TestCase
{
    private function offlinePath(): string
    {
        return parse_url($this->app()->getContainer()->make(UrlGenerator::class)->to('forum')->route('offline'), PHP_URL_PATH);
    }

    #[Test]
    public function the_service_worker_shows_the_offline_page_when_a_page_cant_be_loaded(): void
    {
        $response = $this->send($this->request('GET', '/sw'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('text/javascript; charset=utf-8', $response->getHeaderLine('Content-Type'));
        $this->assertSame('no-cache', $response->getHeaderLine('Cache-Control'));

        $script = (string) $response->getBody();

        $this->assertStringStartsWith(file_get_contents(__DIR__.'/../../../js/serviceWorker/offlinePage.js'), $script);
        // Without the domain, so it's stored from whichever one the visitor is on.
        $this->assertMatchesRegularExpression('#offlinePage\({"url":"'.preg_quote($this->offlinePath(), '#').'","version":"[0-9a-f]{8}"}\);#', $script);
    }

    #[Test]
    public function the_offline_page_says_the_forum_cant_be_reached(): void
    {
        $this->setting('forum_title', 'Acme Community');

        $response = $this->send($this->request('GET', '/offline'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame('noindex', $response->getHeaderLine('X-Robots-Tag'));

        $page = (string) $response->getBody();

        $this->assertStringContainsString('No connection - Acme Community</title>', $page);
        $this->assertStringContainsString('Acme Community can&#039;t be reached.', $page);
        // It checks the forum can be reached again before loading the page.
        $this->assertStringContainsString('fetch('.json_encode($this->offlinePath()), $page);
    }

    #[Test]
    public function the_service_worker_changes_with_the_offline_page(): void
    {
        $page = (string) $this->send($this->request('GET', '/offline'))->getBody();
        $script = (string) $this->send($this->request('GET', '/sw'))->getBody();

        // A changed script is what makes browsers install the worker again, and
        // store the new page.
        $this->assertStringContainsString('"version":"'.substr(md5($page), 0, 8).'"', $script);
    }
}
