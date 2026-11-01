<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\OpenSearch;

use Flarum\Forum\Branding\Branding;
use Flarum\Forum\Branding\IconSet;
use Flarum\Forum\Branding\ShortName;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;
use XMLWriter;

/**
 * The OpenSearch description, which lets browsers add the forum as a search
 * engine and suggest its discussions as someone types.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/XML/Guides/OpenSearch
 */
class OpenSearchDescription
{
    public const MAX_DESCRIPTION_LENGTH = 1024;

    protected Cloud $assets;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected ShortName $shortName,
        protected Branding $branding,
        protected IconSet $icons,
        protected UrlGenerator $url,
        Factory $filesystemFactory
    ) {
        $this->assets = $filesystemFactory->disk('flarum-assets');
    }

    public function build(): string
    {
        $forum = $this->url->to('forum')->base();

        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');

        $xml->startElement('OpenSearchDescription');
        $xml->writeAttribute('xmlns', 'http://a9.com/-/spec/opensearch/1.1/');
        $xml->writeAttribute('xmlns:moz', 'http://www.mozilla.org/2006/browser/search/');

        $xml->writeElement('ShortName', $this->shortName->get());
        $xml->writeElement('Description', $this->description());
        $xml->writeElement('Tags', 'flarum discussion forum');
        $xml->writeElement('InputEncoding', 'UTF-8');

        foreach ($this->images() as [$width, $height, $type, $data]) {
            $xml->startElement('Image');
            $xml->writeAttribute('width', (string) $width);
            $xml->writeAttribute('height', (string) $height);
            $xml->writeAttribute('type', $type);
            $xml->text("data:$type;base64,".base64_encode($data));
            $xml->endElement();
        }

        $this->url($xml, 'text/html', $this->searchPage().'?q={searchTerms}');
        $this->url($xml, 'application/x-suggestions+json', $this->url->to('api')->route('opensearch.suggestions').'?q={searchTerms}');
        $this->url($xml, 'application/opensearchdescription+xml', $this->url->to('forum')->route('opensearch'), 'self');

        // The forum's main page, which Firefox opens for a search with no terms.
        $xml->writeElement('moz:SearchForm', "$forum/");

        // A query for clients to check the search works: the forum's name is
        // the term most likely to have results in any language.
        $xml->startElement('Query');
        $xml->writeAttribute('role', 'example');
        $xml->writeAttribute('searchTerms', (string) $this->settings->get('forum_title'));
        $xml->endElement();

        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    /**
     * The discussion list, where a search runs. The frontend serves it at the
     * forum's root when it is the homepage, and then has nothing at /all.
     * Any other homepage ignores the query.
     */
    protected function searchPage(): string
    {
        if ($this->settings->get('default_route') === '/all') {
            return $this->url->to('forum')->base().'/';
        }

        return $this->url->to('forum')->route('index');
    }

    protected function url(XMLWriter $xml, string $type, string $template, ?string $rel = null): void
    {
        $xml->startElement('Url');
        $xml->writeAttribute('type', $type);

        if ($rel) {
            $xml->writeAttribute('rel', $rel);
        }

        $xml->writeAttribute('template', $template);
        $xml->endElement();
    }

    protected function description(): string
    {
        $description = trim((string) preg_replace('/\s+/u', ' ', strip_tags((string) $this->settings->get('forum_description'))));

        return mb_substr($description ?: (string) $this->settings->get('forum_title'), 0, self::MAX_DESCRIPTION_LENGTH);
    }

    /**
     * The favicon, embedded rather than linked: Firefox refuses linked icons
     * over 10KB, and an embedded one doesn't depend on where assets are served.
     *
     * @return list<array{0: int, 1: int, 2: string, 3: string}> width, height, type and data
     */
    protected function images(): array
    {
        $path = $this->branding->faviconPath();

        if (! $path || ! $this->assets->exists($path)) {
            return [];
        }

        $data = (string) $this->assets->get($path);
        $size = @getimagesizefromstring($data);

        if (! $size) {
            return [];
        }

        if ($size[2] === IMAGETYPE_ICO) {
            return [[16, 16, 'image/x-icon', $data]];
        }

        $favicon = $this->icons->favicon($data);
        $png = getimagesizefromstring($favicon['png']);

        return [
            [16, 16, 'image/x-icon', $favicon['ico']],
            [$png[0], $png[1], 'image/png', $favicon['png']],
        ];
    }
}
