<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\ExtensionManager\Work;

use DOMDocument;
use DOMElement;
use DOMNode;
use Flarum\ExtensionManager\Config\AuthTokens;

/**
 * What an update gives you: every GitHub release between the installed version
 * and the one on offer.
 *
 * 🚨 Every release in the range, not just the newest. Somebody going from
 * 1.2.0 to 1.5.0 gets three releases' worth of changes, and the one that
 * matters (a breaking change in 1.3.0) is rarely in the newest release's notes.
 *
 * 🚨 The repository comes from the saved update check, never from the browser.
 * The request names a package and nothing else, so this can only ever ask
 * GitHub about a repository Packagist or the site's own configuration named.
 *
 * One request to GitHub when somebody asks, cached for six hours. GitHub
 * renders the Markdown itself (`html+json`). The HTML is then cut down to a
 * short list of tags before it reaches the admin page.
 */
class ReleaseNotes
{
    private const ALLOWED = [
        'p', 'br', 'hr', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li',
        'a', 'strong', 'b', 'em', 'i', 'code', 'pre', 'blockquote', 'del',
        'table', 'thead', 'tbody', 'tr', 'th', 'td', 'details', 'summary',
    ];

    public function __construct(
        private string $cacheDir,
        private AuthTokens $auth,
        private int $freshFor = 21600,
        private int $timeout = 15,
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
        $cache = $this->cacheDir.'/'.sha1($repo.'|'.$from.'|'.$to).'.json';

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
                'url' => (string) ($release['html_url'] ?? $url),
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
    private function inRange(string $tag, string $from, string $to): bool
    {
        $v = ltrim($tag, 'v');

        return ($from === '' || version_compare($v, ltrim($from, 'v')) > 0)
            && ($to === '' || version_compare($v, ltrim($to, 'v')) <= 0);
    }

    /**
     * Release notes as HTML the admin page can show: only the tags above, only
     * http(s) links, no attributes but a link's href. Anything else is unwrapped
     * to its text, apart from content that should never be shown at all.
     *
     * GitHub already sanitises what it renders. This is the second check, made
     * here, because the page it lands on is an administrator's.
     */
    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $doc = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="mw-root">'.$html.'</div>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('mw-root');

        if (! $root) {
            return '';
        }

        self::walk($root);

        $out = '';

        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        return $out;
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'template', 'noscript', 'img', 'video', 'audio', 'picture', 'source'], true)) {
                $node->removeChild($child);
                continue;
            }

            self::walk($child);

            if (! in_array($tag, self::ALLOWED, true)) {
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }

                $node->removeChild($child);
                continue;
            }

            $href = $tag === 'a' ? $child->getAttribute('href') : '';

            foreach (iterator_to_array($child->attributes) as $attribute) {
                $child->removeAttribute($attribute->nodeName);
            }

            if ($tag === 'a') {
                if (preg_match('#^https?://#i', $href)) {
                    $child->setAttribute('href', $href);
                    $child->setAttribute('target', '_blank');
                    $child->setAttribute('rel', 'noopener noreferrer nofollow');
                }
            }
        }
    }

    private function get(string $url): ?string
    {
        $headers = "User-Agent: the Extension Manager\r\nAccept: application/vnd.github.html+json\r\n";
        $header = $this->auth->headerFor('github.com');

        if ($header !== null) {
            $headers .= $header."\r\n";
        }

        $body = @file_get_contents($url, false, stream_context_create(['http' => [
            'timeout' => $this->timeout,
            'header' => $headers,
            'ignore_errors' => true,
        ]]));

        return is_string($body) ? $body : null;
    }
}
