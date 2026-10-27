<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use DOMCdataSection;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Flarum\ExtensionManager\Config\AuthTokens;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * What an update gives you: every GitHub release between the installed version
 * and the one on offer.
 *
 * Every release in the range, not just the newest. Somebody going from
 * 1.2.0 to 1.5.0 gets three releases' worth of changes, and the one that
 * matters (a breaking change in 1.3.0) is rarely in the newest release's notes.
 *
 * The repository comes from the saved update check, never from the browser.
 * The request names a package and nothing else, so this can only ever ask
 * GitHub about a repository Packagist or the site's own configuration named.
 *
 * One request to GitHub when somebody asks, cached for six hours. GitHub
 * renders the Markdown itself (`html+json`). The HTML is then cut down to a
 * short list of tags before it reaches the admin page.
 */
class ReleaseNotes
{
    protected const ALLOWED = [
        'p', 'br', 'hr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li',
        'a', 'strong', 'b', 'em', 'i', 'code', 'pre', 'blockquote', 'del',
        'table', 'thead', 'tbody', 'tr', 'th', 'td', 'details', 'summary',
    ];

    /**
     * Removed with their content: active or embedded content, form controls,
     * foreign markup (svg, math), and the raw-text elements whose content was
     * never meant to be read as text.
     */
    protected const DROPPED = [
        'script', 'style', 'iframe', 'frame', 'frameset', 'object', 'embed', 'applet', 'param',
        'form', 'input', 'button', 'textarea', 'select', 'option', 'svg', 'math', 'template',
        'noscript', 'noembed', 'noframes', 'xmp', 'plaintext', 'listing', 'title', 'head',
        'img', 'video', 'audio', 'picture', 'source', 'track', 'canvas', 'map', 'area',
        'base', 'link', 'meta',
    ];

    /** Part of the cache key, so notes cleaned by an earlier rule are not served after it changes. */
    protected const CLEAN_VERSION = 2;

    public function __construct(
        protected string $cacheDir,
        protected AuthTokens $auth,
        protected ClientInterface $http,
        protected int $freshFor = 21600,
        protected int $timeout = 15,
    ) {
    }

    /**
     * @param array{from?:string,to?:string,notes?:string} $update an entry from the update check
     * @param callable(string):?string|null $get overridable so this is testable without a network
     * @return array{url:string,releases:list<array{name:string,tag:string,url:string,publishedAt:?string,html:string}>}|null
     *         null when the update has no GitHub release page to start from
     */
    public function between(array $update, ?callable $get = null): ?array
    {
        $url = (string) ($update['notes'] ?? '');

        if (! preg_match('#^https://github\.com/([\w.-]+)/([\w.-]+)/releases/tag/#', $url, $m)) {
            return null;
        }

        $repo = $m[1].'/'.$m[2];
        $from = (string) ($update['from'] ?? '');
        $to = (string) ($update['to'] ?? '');
        $cache = $this->cacheDir.'/'.sha1(self::CLEAN_VERSION.'|'.$repo.'|'.$from.'|'.$to).'.json';

        if (is_file($cache) && time() - (int) filemtime($cache) < $this->freshFor) {
            $hit = json_decode((string) file_get_contents($cache), true);

            if (is_array($hit)) {
                return $hit;
            }
        }

        $body = ($get ?? fn (string $u) => $this->get($u))('https://api.github.com/repos/'.$repo.'/releases?per_page=100');
        $list = is_string($body) ? json_decode($body, true) : null;

        // Not cached: GitHub being down or rate-limiting should not stick for six hours.
        if (! is_array($list) || ! array_is_list($list)) {
            return ['url' => $url, 'releases' => []];
        }

        $releases = [];

        foreach ($list as $release) {
            $tag = is_array($release) ? (string) ($release['tag_name'] ?? '') : '';

            if ($tag === '' || ! empty($release['draft']) || ! $this->inRange($tag, $from, $to)) {
                continue;
            }

            $releases[] = [
                'name' => trim((string) ($release['name'] ?? '')) ?: $tag,
                'tag' => $tag,
                // Shown as a link too, so held to the same rule as the page it came from.
                'url' => preg_match('#^https://github\.com/[^\s"<>]*$#', (string) ($release['html_url'] ?? '')) ? (string) $release['html_url'] : $url,
                'publishedAt' => isset($release['published_at']) ? (string) $release['published_at'] : null,
                'html' => self::clean((string) ($release['body_html'] ?? '')),
            ];
        }

        usort($releases, fn ($a, $b) => version_compare(ltrim($b['tag'], 'v'), ltrim($a['tag'], 'v')));

        $result = ['url' => $url, 'releases' => array_slice($releases, 0, 20)];

        if (! is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }

        @file_put_contents($cache, json_encode($result, JSON_UNESCAPED_SLASHES));

        return $result;
    }

    /** Newer than what is installed, and no newer than what is on offer. */
    protected function inRange(string $tag, string $from, string $to): bool
    {
        $v = ltrim($tag, 'v');

        return ($from === '' || version_compare($v, ltrim($from, 'v')) > 0)
            && ($to === '' || version_compare($v, ltrim($to, 'v')) <= 0);
    }

    /**
     * Release notes as HTML the admin page can show: only the tags in ALLOWED,
     * no attributes but a link's http(s) href, and every piece of text escaped.
     * An element not on the list is unwrapped to its text, and one in DROPPED
     * goes with everything inside it.
     *
     * GitHub already sanitises what it renders. This is the second check, made
     * here, because the page it lands on is an administrator's and shows it
     * with m.trust(). So the output is written out here, tag by tag, rather
     * than by libxml's serialiser: libxml keeps the text of raw-text elements
     * such as xmp, noembed, noframes and plaintext unescaped wherever that text
     * is moved to, and a browser then parses it as markup.
     */
    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><body>'.$html.'</body>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $body = $doc->getElementsByTagName('body')->item(0);

        return $body ? self::render($body) : '';
    }

    protected static function render(DOMNode $node): string
    {
        $out = '';

        foreach ($node->childNodes as $child) {
            // CDATA extends DOMText, and is never text a release page means to show.
            if ($child instanceof DOMText && ! $child instanceof DOMCdataSection) {
                $out .= self::escape($child->data);
                continue;
            }

            // Comments, processing instructions and anything else that is not an element.
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROPPED, true)) {
                continue;
            }

            $inner = self::render($child);

            if (! in_array($tag, self::ALLOWED, true)) {
                $out .= $inner;
                continue;
            }

            if ($tag === 'br' || $tag === 'hr') {
                $out .= '<'.$tag.'>';
                continue;
            }

            $out .= '<'.$tag.($tag === 'a' ? self::link($child->getAttribute('href')) : '').'>'.$inner.'</'.$tag.'>';
        }

        return $out;
    }

    /** A link's attributes: an absolute http(s) URL with nothing a browser would strip or reinterpret, or none. */
    protected static function link(string $href): string
    {
        if (! preg_match('#^https?://[^/?\#\s]#i', $href) || preg_match('/[\x00-\x20\x7f]/', $href)) {
            return '';
        }

        return ' href="'.self::escape($href).'" target="_blank" rel="noopener noreferrer nofollow"';
    }

    protected static function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }

    protected function get(string $url): ?string
    {
        $authorization = $this->auth->authorizationFor($url);

        try {
            return (string) $this->http->request('GET', $url, [
                'timeout' => $this->timeout,
                'http_errors' => false,
                'allow_redirects' => ['max' => 5, 'protocols' => ['https'], 'referer' => false],
                'headers' => ['User-Agent' => 'The Extension Manager', 'Accept' => 'application/vnd.github.html+json']
                    + ($authorization !== null ? ['Authorization' => $authorization] : []),
            ])->getBody();
        } catch (GuzzleException) {
            return null;
        }
    }
}
