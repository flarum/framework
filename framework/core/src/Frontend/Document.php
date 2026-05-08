<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Formatter\XsltPolyfill;
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
     *
     * @var null|string
     */
    public $title;

    /**
     * The language of the document, displayed as the value of the attribute `lang` in the <html> tag.
     *
     * @var null|string
     */
    public $language;

    /**
     * The text direction of the document, displayed as the value of the attribute `dir` in the <html> tag.
     *
     * @var null|string
     */
    public $direction;

    /**
     * The name of the frontend app view to display.
     *
     * @var string
     */
    public $appView = 'flarum::frontend.app';

    /**
     * The name of the frontend layout view to display.
     *
     * @var string
     */
    public $layoutView;

    /**
     * The name of the frontend content view to display.
     *
     * @var string
     */
    public $contentView = 'flarum::frontend.content';

    /**
     * The SEO content of the page, displayed within the layout in <noscript> tags.
     *
     * @var string|Renderable
     */
    public $content;

    /**
     * Other variables to preload into the Flarum JS.
     *
     * @var array
     */
    public $payload = [];

    /**
     * An array of meta tags to append to the page's <head>.
     *
     * @var array
     */
    public $meta = [];

    /**
     * The canonical URL for this page.
     *
     * This will signal to search engines what URL should be used for this
     * content, if it can be found under multiple addresses. This is an
     * important tool to tackle duplicate content.
     *
     * @var null|string
     */
    public $canonicalUrl;

    /**
     * Which page of content are we on?
     *
     * This is used to build prev/next meta links for SEO.
     *
     * @var null|int
     */
    public $page;

    /**
     * Is there a next page?
     *
     * This is used with $page to build next meta links for SEO.
     *
     * @var null|bool
     */
    public $hasNextPage;

    /**
     * An array of strings to append to the page's <head>.
     *
     * @var array
     */
    public $head = [];

    /**
     * An array of strings to prepend before the page's </body>.
     *
     * @var array
     */
    public $foot = [];

    /**
     * An array of JavaScript URLs to load.
     *
     * @var array
     */
    public $js = [];

    /**
     * An array of CSS URLs to load.
     *
     * @var array
     */
    public $css = [];

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
    public $preloads = [];

    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var array
     */
    protected $forumApiDocument;

    /**
     * @var Request
     */
    protected $request;

    public function __construct(Factory $view, array $forumApiDocument, Request $request)
    {
        $this->view = $view;
        $this->forumApiDocument = $forumApiDocument;
        $this->request = $request;
    }

    /**
     * @return string
     */
    public function render(): string
    {
        $this->view->share('forum', Arr::get($this->forumApiDocument, 'data.attributes'));

        return $this->makeView()->render();
    }

    /**
     * @return View
     */
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
        ]);
    }

    /**
     * @return string
     */
    protected function makeTitle(): string
    {
        // @todo v2.0 inject as dependency instead
        return resolve(TitleDriverInterface::class)->makeTitle($this, $this->request, $this->forumApiDocument);
    }

    protected function makeLayout(): ?View
    {
        if ($this->layoutView) {
            return $this->view->make($this->layoutView)->with('content', $this->makeContent());
        }

        return null;
    }

    /**
     * @return View
     */
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

    /**
     * @return string
     */
    protected function makeHead(): string
    {
        $head = array_map(function ($url) {
            return '<link rel="stylesheet" href="'.e($url).'">';
        }, $this->css);

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
     *
     * @return string|null
     */
    private function makeXsltPolyfillLoader()
    {
        // @todo v2.0 inject FilesystemFactory as dependency instead
        $url = XsltPolyfill::publicUrl(resolve(FilesystemFactory::class));
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

    /**
     * @return string
     */
    protected function makeJs(): string
    {
        return implode("\n", array_map(function ($url) {
            return '<script src="'.e($url).'"></script>';
        }, $this->js));
    }

    /**
     * @return string
     */
    protected function makeFoot(): string
    {
        return implode("\n", $this->foot);
    }

    /**
     * @return array
     */
    public function getForumApiDocument(): array
    {
        return $this->forumApiDocument;
    }

    /**
     * @param array $forumApiDocument
     */
    public function setForumApiDocument(array $forumApiDocument)
    {
        $this->forumApiDocument = $forumApiDocument;
    }

    public static function setPageParam(string $url, ?int $page)
    {
        if (! $page || $page === 1) {
            return self::setQueryParam($url, 'page', null);
        }

        return self::setQueryParam($url, 'page', (string) $page);
    }

    /**
     * Set or override a query param on a string URL to a particular value.
     */
    protected static function setQueryParam(string $url, string $key, ?string $value)
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
        } else {
            return $url;
        }
    }
}
