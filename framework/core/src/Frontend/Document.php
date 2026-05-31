<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Formatter\XsltPolyfill;
use Flarum\Foundation\Config;
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
     * An array of strings to append to the page's <head>, rendered *after*
     * the forum/admin stylesheet so that user-supplied <style>/<link> blocks
     * correctly override core CSS.
     */
    public array $head = [];

    /**
     * An array of strings to render in <head> *before* the forum/admin
     * stylesheet. Use this for hints and scripts that must take effect
     * before first paint — preconnect hints to additional origins, an
     * inline script that sets data-theme on <html>, etc.
     *
     * Anything that depends on CSS variables, computed styles, or that
     * should be able to override the forum stylesheet belongs in $head.
     */
    public array $preHead = [];

    /**
     * Inline critical CSS emitted before the main stylesheet link.
     * Populated by FrontendServiceProvider to give the browser a
     * theme-accurate body background to paint while the main stylesheet
     * is being fetched.
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

    public function __construct(
        protected Factory $view,
        protected array $forumApiDocument,
        protected Request $request,
        protected TitleDriverInterface $titleDriver,
        protected Config $config,
        /**
         * We need the versioner to get the revisions of split chunks.
         */
        protected VersionerInterface $versioner,
        protected FilesystemFactory $filesystem,
    ) {
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
        // The forum/admin stylesheet is render-blocking (parser-discovered
        // <link rel="stylesheet">), so anything that must be in effect *before*
        // first paint — preconnect hints, and anything pushed to $preHead by
        // content callbacks (e.g. the inline data-theme script) — precedes it.
        // Everything else — extension head content, JS preloads, meta tags,
        // polyfills — comes after so it doesn't delay paint and so user
        // overrides like custom <style> blocks correctly win the cascade.
        $head = $this->makePreconnects();
        $head = array_merge($head, $this->preHead);

        foreach ($this->css as $url) {
            $head[] = '<link rel="stylesheet" href="'.e($url).'" fetchpriority="high">';
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

        if ($polyfill = $this->makeXsltPolyfillLoader()) {
            $head[] = $polyfill;
        }

        return implode("\n", array_merge($head, $this->head));
    }

    /**
     * Emit a tiny inline detector that synchronously document.write()s a
     * <script src="…xslt-polyfill.min.js"> tag if the browser has no
     * working XSLTProcessor. Because document.write of a script tag during
     * HTML parsing inserts it inline, the parser blocks until the polyfill
     * loads and executes — this guarantees window.XSLTProcessor is in
     * place before forum.js runs (s9e calls `new XSLTProcessor` at
     * top-level module load).
     *
     * Browsers with native XSLT pay the cost of the detector only (~200
     * bytes); only affected browsers fetch the polyfill itself.
     */
    private function makeXsltPolyfillLoader(): ?string
    {
        $url = XsltPolyfill::publicUrl($this->filesystem);
        if ($url === null) {
            return null;
        }

        // JSON-encode the URL with HTML-safe flags so it can't break out of
        // the JS string context, even if a hostile asset URL contained
        // quotes / angle brackets / ampersands. The JSON-encoded value is
        // already a JS string literal (with surrounding quotes), so it can
        // be concatenated into the document.write() argument directly.
        $jsUrl = json_encode($url, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        // The closing </script> for the written-out tag is split across the
        // string literal so the *outer* <script> doesn't close early when
        // the HTML parser scans for </script>.
        return <<<HTML
<script>(function(){try{if(typeof XSLTProcessor!=="undefined"&&new XSLTProcessor())return;}catch(e){}document.write('<script src='+$jsUrl+'><\/script>');})();</script>
HTML;
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
