<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Foundation\Config;
use Flarum\Frontend\Compiler\FileVersioner;
use Flarum\Frontend\Compiler\VersionerInterface;
use Flarum\Frontend\Driver\TitleDriverInterface;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * A view which renders a HTML skeleton for Flarum's frontend app.
 */
class Document implements Renderable
{
    /**
     * The title of the document, displayed in the <title> tag.
     */
    public ?string $title = null;

    /**
     * The language of the document, displayed as the value of the attribute `lang` in the <html> tag.
     */
    public ?string $language = null;

    /**
     * The text direction of the document, displayed as the value of the attribute `dir` in the <html> tag.
     */
    public ?string $direction = null;

    /**
     * The name of the frontend app view to display.
     */
    public string $appView = 'flarum::frontend.app';

    /**
     * The name of the frontend layout view to display.
     */
    public string $layoutView;

    /**
     * The name of the frontend content view to display.
     */
    public string $contentView = 'flarum::frontend.content';

    /**
     * The SEO content of the page, displayed within the layout in <noscript> tags.
     */
    public string|Renderable|null $content = null;

    /**
     * Other variables to preload into the Flarum JS.
     */
    public array $payload = [];

    /**
     * An array of meta tags to append to the page's <head>.
     */
    public array $meta = [];

    /**
     * The canonical URL for this page.
     *
     * This will signal to search engines what URL should be used for this
     * content, if it can be found under multiple addresses. This is an
     * important tool to tackle duplicate content.
     */
    public ?string $canonicalUrl = null;

    /**
     * Which page of content are we on?
     *
     * This is used to build prev/next meta links for SEO.
     */
    public ?int $page = null;

    /**
     * Is there a next page?
     *
     * This is used with $page to build next meta links for SEO.
     */
    public ?bool $hasNextPage = null;

    /**
     * An array of strings to append to the page's <head>.
     */
    public array $head = [];

    /**
     * Inline critical CSS emitted before the async stylesheet links.
     * Populated by FrontendServiceProvider to prevent a flash of unstyled content.
     */
    public string $criticalCss = '';

    /**
     * An array of strings to prepend before the page's </body>.
     */
    public array $foot = [];

    /**
     * An array of JavaScript URLs to load.
     */
    public array $js = [];

    /**
     * An array of CSS URLs to load.
     */
    public array $css = [];

    /**
     * An array of preloaded assets.
     *
     * Each array item should be an array containing keys that pertain to the
     * `<link rel="preload">` tag.
     *
     * For example, the following will add a preload tag for a FontAwesome font file:
     * ```
     * $this->preloads[] = [
     *   'href' => '/assets/fonts/fa-solid-900.woff2',
     *   'as' => 'font',
     *   'type' => 'font/woff2',
     *   'crossorigin' => ''
     * ];
     * ```
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Link_types/preload
     *
     * @var array
     */
    public array $preloads = [];

    /**
     * Document extra attributes.
     *
     * @var array<string, string|callable|array>
     */
    public array $extraAttributes = [
        'class' => [],
    ];

    /**
     * We need the versioner to get the revisions of split chunks.
     */
    protected VersionerInterface $versioner;

    public function __construct(
        protected Factory $view,
        protected array $forumApiDocument,
        protected Request $request,
        protected TitleDriverInterface $titleDriver,
        protected Config $config,
        FilesystemFactory $filesystem
    ) {
        $this->versioner = new FileVersioner(
            $filesystem->disk('flarum-assets')
        );
    }

    public function render(): string
    {
        $this->view->share('forum', Arr::get($this->forumApiDocument, 'data.attributes'));

        return $this->makeView()->render();
    }

    protected function makeView(): View
    {
        return $this->view->make($this->appView)->with([
            'title' => $this->makeTitle(),
            'payload' => $this->payload,
            'layout' => $this->makeLayout(),
            'language' => $this->language,
            'direction' => $this->direction,
            'js' => $this->makeJs(),
            'head' => $this->makeHead(),
            'foot' => $this->makeFoot(),
            'criticalCss' => $this->criticalCss,
            'extraAttributes' => $this->makeExtraAttributes(),
            'extraClasses' => $this->makeExtraClasses(),
            'revisions' => $this->versioner->allRevisions(),
            'debug' => $this->config->inDebugMode(),
        ]);
    }

    protected function makeTitle(): string
    {
        return $this->titleDriver->makeTitle($this, $this->request, $this->forumApiDocument);
    }

    protected function makeLayout(): ?View
    {
        if ($this->layoutView) {
            return $this->view->make($this->layoutView)->with('content', $this->makeContent());
        }

        return null;
    }

    protected function makeContent(): View
    {
        return $this->view->make($this->contentView)->with('content', $this->content);
    }

    protected function makePreloads(): array
    {
        return array_map(function ($preload) {
            $attributes = '';

            foreach ($preload as $key => $value) {
                $attributes .= " $key=\"".e($value).'"';
            }

            return "<link rel=\"preload\"$attributes>";
        }, $this->preloads);
    }

    protected function makeExtraClasses(): array
    {
        $classes = [];

        $extraClasses = $this->extraAttributes['class'] ?? [];

        foreach ($extraClasses as $class) {
            if (is_callable($class)) {
                $class = $class($this->request);
            }

            $classes = array_merge($classes, (array) $class);
        }

        return $classes;
    }

    protected function makeExtraAttributes(): string
    {
        $attributes = [];

        foreach ($this->extraAttributes as $key => $value) {
            if ($key === 'class') {
                continue;
            }

            if (is_callable($value)) {
                $value = $value($this->request);
            }

            $attributes[$key] = $value;
        }

        return array_reduce(array_keys($attributes), function (string $carry, string $key) use ($attributes): string {
            $value = $attributes[$key];

            if (is_array($value)) {
                $value = implode(' ', $value);
            }

            return $carry.' '.$key.'="'.e($value).'"';
        }, '');
    }

    protected function makePreconnects(): array
    {
        $forumOrigin = $this->config->url()->getScheme().'://'.$this->config->url()->getHost();

        $urls = array_merge($this->css, $this->js, array_column($this->preloads, 'href'));

        $seen = [];
        $tags = [];

        foreach ($urls as $url) {
            if (empty($url)) {
                continue;
            }

            $parts = parse_url($url);

            if (empty($parts['host'])) {
                continue;
            }

            $origin = $parts['scheme'].'://'.$parts['host'];

            if ($origin === $forumOrigin || isset($seen[$origin])) {
                continue;
            }

            $seen[$origin] = true;
            $escaped = e($origin);
            $tags[] = '<link rel="preconnect" href="'.$escaped.'" crossorigin>';
            $tags[] = '<link rel="dns-prefetch" href="'.$escaped.'">';
        }

        return $tags;
    }

    protected function makeHead(): string
    {
        // On warm visits (CSS already cached), a tiny inline script injects blocking
        // <link rel="stylesheet"> tags synchronously before first paint — no FOUC, no
        // network round-trip. On cold visits the sessionStorage keys are absent so the
        // script exits immediately and the async preload path below takes over.
        // Versioned URLs act as natural cache-busters: a new deploy changes the URL,
        // the old sessionStorage key doesn't match, and the async path runs once more.
        $head = $this->makePreconnects();

        if (! empty($this->css)) {
            $cssJson = json_encode(array_values($this->css), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
            $head[] = '<script>(function(){var s='.$cssJson.';if(s.every(function(h){return sessionStorage.getItem("css:"+h);})){s.forEach(function(h){var l=document.createElement("link");l.rel="stylesheet";l.href=h;document.head.appendChild(l);});}Object.keys(sessionStorage).forEach(function(k){if(k.indexOf("css:")===0&&s.indexOf(k.slice(4))===-1){sessionStorage.removeItem(k);}});})();</script>';
        }

        // Async preload path for cold visits. The onload updates sessionStorage so the
        // next page load can take the fast synchronous path above.
        foreach ($this->css as $url) {
            $escaped = e($url);
            $head[] = '<link rel="preload" href="'.$escaped.'" as="style" fetchpriority="high" onload="sessionStorage.setItem(\'css:\'+this.href,\'1\');this.onload=null;this.rel=\'stylesheet\'">'
                .'<noscript><link rel="stylesheet" href="'.$escaped.'"></noscript>';
        }

        if ($this->page) {
            if ($this->page > 1) {
                $head[] = '<link rel="prev" href="'.e(self::setPageParam($this->canonicalUrl, $this->page - 1)).'">';
            }
            if ($this->hasNextPage) {
                $head[] = '<link rel="next" href="'.e(self::setPageParam($this->canonicalUrl, $this->page + 1)).'">';
            }
        }

        if ($this->canonicalUrl) {
            $head[] = '<link rel="canonical" href="'.e(self::setPageParam($this->canonicalUrl, $this->page)).'">';
        }

        $head = array_merge($head, $this->makePreloads());

        $head = array_merge($head, array_map(function ($content, $name) {
            return '<meta name="'.e($name).'" content="'.e($content).'">';
        }, $this->meta, array_keys($this->meta)));

        return implode("\n", array_merge($head, $this->head));
    }

    protected function makeJs(): string
    {
        return implode("\n", array_map(function ($url) {
            return '<script src="'.e($url).'"></script>';
        }, $this->js));
    }

    protected function makeFoot(): string
    {
        return implode("\n", $this->foot);
    }

    public function getForumApiDocument(): array
    {
        return $this->forumApiDocument;
    }

    public function setForumApiDocument(array $forumApiDocument): void
    {
        $this->forumApiDocument = $forumApiDocument;
    }

    public static function setPageParam(string $url, ?int $page): string
    {
        if (! $page || $page === 1) {
            return self::setQueryParam($url, 'page', null);
        }

        return self::setQueryParam($url, 'page', (string) $page);
    }

    /**
     * Set or override a query param on a string URL to a particular value.
     */
    protected static function setQueryParam(string $url, string $key, ?string $value): string
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $urlParts = parse_url($url);
            if (isset($urlParts['query'])) {
                parse_str($urlParts['query'], $urlQueryArgs);

                if ($value === null) {
                    unset($urlQueryArgs[$key]);
                } else {
                    $urlQueryArgs[$key] = $value;
                }

                $urlParts['query'] = http_build_query($urlQueryArgs);
                $newUrl = $urlParts['scheme'].'://'.$urlParts['host'].$urlParts['path'].'?'.$urlParts['query'];
            } elseif ($value !== null) {
                $newUrl = $url.'?'.http_build_query([$key => $value]);
            } else {
                return $url;
            }

            return $newUrl;
        }

        return $url;
    }
}
