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
use Flarum\Forum\Branding\SplashScreen;
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
    private SplashScreen $splashScreen;

    public function __construct(
        private LocaleManager $locales,
        ?Branding $branding = null,
        ?WebManifest $manifest = null,
        ?UrlGenerator $url = null,
        ?ShortName $shortName = null,
        ?SplashScreen $splashScreen = null
    ) {
        $this->branding = $branding ?? resolve(Branding::class);
        $this->manifest = $manifest ?? resolve(WebManifest::class);
        $this->url = $url ?? resolve(UrlGenerator::class);
        $this->shortName = $shortName ?? resolve(ShortName::class);
        $this->splashScreen = $splashScreen ?? resolve(SplashScreen::class);
    }

    public function __invoke(Document $document, Request $request): void
    {
        $document->language = $this->locales->getLocale();
        $document->direction = $this->locales->getDirection();

        $splashScreens = $this->splashScreen->links();

        $document->meta = array_merge($document->meta, $this->buildMeta($document, $this->manifest->opensAsApp()));
        $document->head = array_merge($document->head, $this->buildHead($document, $splashScreens));
    }

    private function buildMeta(Document $document, bool $opensAsApp): array
    {
        $forumApiDocument = $document->getForumApiDocument();

        $meta = [
            'viewport' => 'width=device-width, initial-scale=1',
            'description' => Arr::get($forumApiDocument, 'data.attributes.description'),
            'theme-color' => Arr::get($forumApiDocument, 'data.attributes.themePrimaryColor')
        ];

        // Older iOS versions only open a forum added to the home screen as an
        // app, and show its splash screen, when the page asks for it. Chrome
        // warns about Apple's tag without the standard one.
        if ($opensAsApp) {
            $meta['apple-mobile-web-app-capable'] = 'yes';
            $meta['mobile-web-app-capable'] = 'yes';
        }

        return $meta;
    }

    /**
     * @param list<array{href: string, media: string}> $splashScreens
     */
    private function buildHead(Document $document, array $splashScreens): array
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

        if ($splashScreens) {
            $head['apple-touch-startup-image'] = implode("\n", array_map(
                fn (array $link) => '<link rel="apple-touch-startup-image" href="'.e($link['href']).'" media="'.e($link['media']).'">',
                $splashScreens
            ));
        }

        $head['opensearch'] = '<link rel="search" type="application/opensearchdescription+xml" title="'.e($this->shortName->get()).'" href="'.e($this->url->to('forum')->route('opensearch')).'">';

        if ($this->manifest->isLinked()) {
            $head['manifest'] = '<link rel="manifest" href="'.e($this->url->to('forum')->route('manifest')).'">';
        }

        return $head;
    }
}
