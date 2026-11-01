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
use DOMDocument;
use DOMElement;

/**
 * The OpenSearch description, which lets browsers add the forum as a search
 * engine and suggest its discussions as someone types.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/XML/Guides/OpenSearch
 */
class OpenSearchDescription
{
    public const MAX_DESCRIPTION_LENGTH = 1024;

    protected const NS = 'http://a9.com/-/spec/opensearch/1.1/';

    protected const MOZ_NS = 'http://www.mozilla.org/2006/browser/search/';

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

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $root = $document->appendChild($document->createElementNS(self::NS, 'OpenSearchDescription'));
        $root->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:moz', self::MOZ_NS);

        $this->element($root, 'ShortName', [], $this->shortName->get());
        $this->element($root, 'Description', [], $this->description());
        $this->element($root, 'Tags', [], 'flarum discussion forum');
        $this->element($root, 'InputEncoding', [], 'UTF-8');

        foreach ($this->images() as [$width, $height, $type, $data]) {
            $this->element($root, 'Image', ['width' => (string) $width, 'height' => (string) $height, 'type' => $type], "data:$type;base64,".base64_encode($data));
        }

        $this->element($root, 'Url', ['type' => 'text/html', 'template' => $this->searchPage().'?q={searchTerms}']);
        $this->element($root, 'Url', ['type' => 'application/x-suggestions+json', 'template' => $this->url->to('api')->route('opensearch.suggestions').'?q={searchTerms}']);
        $this->element($root, 'Url', ['type' => 'application/opensearchdescription+xml', 'rel' => 'self', 'template' => $this->url->to('forum')->route('opensearch')]);

        // The forum's main page, which Firefox opens for a search with no terms.
        $root->appendChild($document->createElementNS(self::MOZ_NS, 'moz:SearchForm'))->textContent = "$forum/";

        // A query for clients to check the search works: the forum's name is
        // the term most likely to have results in any language.
        $this->element($root, 'Query', ['role' => 'example', 'searchTerms' => (string) $this->settings->get('forum_title')]);

        return (string) $document->saveXML();
    }

    /**
     * @param array<string, string> $attributes
     */
    protected function element(DOMElement $parent, string $name, array $attributes = [], ?string $text = null): void
    {
        $element = $parent->appendChild($parent->ownerDocument->createElementNS(self::NS, $name));

        foreach ($attributes as $attribute => $value) {
            $element->setAttribute($attribute, $this->xmlText($value));
        }

        if ($text !== null) {
            $element->textContent = $this->xmlText($text);
        }
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

    /**
     * Text without the control characters XML can't contain, which a setting
     * pasted from elsewhere may have.
     */
    protected function xmlText(string $text): string
    {
        return (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
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
