<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Content;

use Flarum\Forum\Branding\Branding;
use Flarum\Forum\Branding\ShortName;
use Flarum\Forum\Branding\WebManifest;
use Flarum\Frontend\Document;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\LocaleManager;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

readonly class Meta
{
    private Branding $branding;
    private WebManifest $manifest;
    private UrlGenerator $url;
    private ShortName $shortName;

    public function __construct(
        private LocaleManager $locales,
        ?Branding $branding = null,
        ?WebManifest $manifest = null,
        ?UrlGenerator $url = null,
        ?ShortName $shortName = null
    ) {
        $this->branding = $branding ?? resolve(Branding::class);
        $this->manifest = $manifest ?? resolve(WebManifest::class);
        $this->url = $url ?? resolve(UrlGenerator::class);
        $this->shortName = $shortName ?? resolve(ShortName::class);
    }

    public function __invoke(Document $document, Request $request): void
    {
        $document->language = $this->locales->getLocale();
        $document->direction = $this->locales->getDirection();

        $document->meta = array_merge($document->meta, $this->buildMeta($document));
        $document->head = array_merge($document->head, $this->buildHead($document));
    }

    private function buildMeta(Document $document): array
    {
        $forumApiDocument = $document->getForumApiDocument();

        return [
            'viewport' => 'width=device-width, initial-scale=1',
            'description' => Arr::get($forumApiDocument, 'data.attributes.description'),
            'theme-color' => Arr::get($forumApiDocument, 'data.attributes.themePrimaryColor')
        ];
    }

    private function buildHead(Document $document): array
    {
        $head = [];

        if ($ico = $this->branding->faviconIcoUrl()) {
            $head['favicon'] = '<link rel="icon" href="'.e($ico).'" sizes="32x32">';
        } elseif ($favicon = $this->branding->faviconUrl()) {
            $head['favicon'] = '<link rel="icon" href="'.e($favicon).'">';
        }

        if ($svg = $this->branding->faviconSvgUrl()) {
            $head['favicon-svg'] = '<link rel="icon" href="'.e($svg).'" type="image/svg+xml">';
        }

        if ($appleTouchIcon = $this->branding->appIconUrls()['apple-touch-icon.png'] ?? null) {
            $head['apple-touch-icon'] = '<link rel="apple-touch-icon" href="'.e($appleTouchIcon).'">';
        }

        $head['opensearch'] = '<link rel="search" type="application/opensearchdescription+xml" title="'.e($this->shortName->get()).'" href="'.e($this->url->to('forum')->route('opensearch')).'">';

        if ($this->manifest->isLinked()) {
            $head['manifest'] = '<link rel="manifest" href="'.e($this->url->to('forum')->route('manifest')).'">';
        }

        return $head;
    }
}
