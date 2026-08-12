<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Formatter;

use Flarum\Foundation\Config;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Contracts\Filesystem\Factory;
use Psr\Http\Message\ServerRequestInterface;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Unparser;
use s9e\TextFormatter\Utils;

class Formatter
{
    protected array $configurationCallbacks = [];
    protected array $parsingCallbacks = [];
    protected array $unparsingCallbacks = [];
    protected array $renderingCallbacks = [];

    /**
     * The forum's own host and base path, worked out once per request.
     *
     * @var array{host: string, path: string}|null
     */
    protected ?array $forumOrigin = null;

    /**
     * The forum's favicon URL, or null if there isn't one.
     *
     * `false` means "not looked up yet", since null is a real answer here.
     */
    protected string|false|null $faviconUrl = false;

    /**
     * `$config` is optional because extensions subclass this and call
     * `parent::__construct()` with the two arguments that existed before it
     * was added. When it is absent the forum's address is resolved from the
     * container instead, so those subclasses keep working untouched.
     */
    public function __construct(
        protected Repository $cache,
        protected string $cacheDir,
        protected ?Config $config = null
    ) {
    }

    /**
     * @internal
     */
    public function addConfigurationCallback(callable $callback): void
    {
        $this->configurationCallbacks[] = $callback;
    }

    /**
     * @internal
     */
    public function addParsingCallback(callable $callback): void
    {
        $this->parsingCallbacks[] = $callback;
    }

    /**
     * @internal
     */
    public function addUnparsingCallback(callable $callback): void
    {
        $this->unparsingCallbacks[] = $callback;
    }

    /**
     * @internal
     */
    public function addRenderingCallback(callable $callback): void
    {
        $this->renderingCallbacks[] = $callback;
    }

    public function parse(string $text, mixed $context = null, ?User $user = null): string
    {
        $parser = $this->getParser($context);

        /*
         * Can be injected in tag or attribute filters by calling:
         * ->addParameterByName('actor') on the filter.
         * See the mentions extension's ConfigureMentions.php for an example.
         */
        $parser->registeredVars['actor'] = $user;

        foreach ($this->parsingCallbacks as $callback) {
            $text = $callback($parser, $context, $text, $user);
        }

        return $parser->parse($text);
    }

    public function render(string $xml, mixed $context = null, ?ServerRequestInterface $request = null): string
    {
        $renderer = $this->getRenderer();

        foreach ($this->renderingCallbacks as $callback) {
            $xml = $callback($renderer, $context, $xml, $request);
        }

        $xml = $this->configureDefaultsOnLinks($xml);

        return $renderer->render($xml);
    }

    public function unparse(?string $xml, mixed $context = null): ?string
    {
        if ($xml === null) {
            return null;
        }

        foreach ($this->unparsingCallbacks as $callback) {
            $xml = $callback($context, $xml);
        }

        return Unparser::unparse($xml);
    }

    /**
     * Flush the cache so that the formatter components are regenerated.
     */
    public function flush(): void
    {
        $this->cache->forget('flarum.formatter');
    }

    protected function getConfigurator(): Configurator
    {
        $configurator = new Configurator;

        $configurator->rootRules->enableAutoLineBreaks();

        $configurator->rendering->setEngine('PHP');
        $configurator->rendering->getEngine()->cacheDir = $this->cacheDir; // @phpstan-ignore-line

        $configurator->enableJavaScript();
        $configurator->javascript->exports = ['preview'];

        $configurator->javascript->setMinifier('MatthiasMullieMinify')
            ->keepGoing = true;

        $configurator->Escaper; /** @phpstan-ignore-line */
        $configurator->Autoemail; /** @phpstan-ignore-line */
        $configurator->Autolink; /** @phpstan-ignore-line */
        $configurator->tags->onDuplicate('replace');

        foreach ($this->configurationCallbacks as $callback) {
            $callback($configurator);
        }

        $this->configureExternalLinks($configurator);

        return $configurator;
    }

    /**
     * Let the link template carry attributes decided when a post is rendered.
     *
     * Whether a link points back at this forum is not known when the post is
     * written — the forum may since have moved — so `rel`, `target` and `class`
     * are worked out at render time and copied through here. A theme that
     * replaces this template needs to copy them too, or links lose the
     * treatment.
     */
    protected function configureExternalLinks(Configurator $configurator): void
    {
        /**
         * @var Configurator\Items\TemplateDocument $dom
         */
        $dom = $configurator->tags['URL']->template->asDOM();

        foreach ($dom->getElementsByTagName('a') as $a) {
            /** @var \s9e\SweetDOM\Element $a */
            $a->prependXslCopyOf('@class');
            $a->prependXslCopyOf('@target');
            $a->prependXslCopyOf('@rel');
        }

        $dom->saveChanges();

        $this->configureDiscussionLinks($configurator);
    }

    /**
     * Show a link to a discussion as the discussion it points at.
     *
     * A pasted address says nothing useful in the middle of a sentence, so
     * where the writer pasted one bare — leaving the address as the link's own
     * text — it is shown as `#123` instead, with the post number alongside it
     * when the link points at a particular reply.
     *
     * When the writer chose their own words for the link, those words are what
     * appears; the label replaces an address, never a sentence.
     */
    protected function configureDiscussionLinks(Configurator $configurator): void
    {
        $tag = $configurator->tags['URL'];

        // Only set when the URL turned out to be a discussion on this forum,
        // which is decided per render rather than when the post was written.
        $tag->attributes->add('discussionid')->required = false;
        $tag->attributes->add('postnumber')->required = false;
        $tag->attributes->add('faviconurl')->required = false;

        $template = (string) $tag->template;

        // The label stands in for the address only when the link's text *is*
        // the address. `[click here](...)` and `<url>` both leave other text
        // in place, and that text is the writer's, so it stays.
        $tag->template =
            '<xsl:choose>'
                .'<xsl:when test="@discussionid and string(.) = @url">'
                .$this->getDiscussionLinkTemplate()
                .'</xsl:when>'
                .'<xsl:otherwise>'.$template.'</xsl:otherwise>'
            .'</xsl:choose>';
    }

    /**
     * The markup for a discussion link that is showing its label.
     */
    protected function getDiscussionLinkTemplate(): string
    {
        return '<a href="{@url}">'
            .'<xsl:copy-of select="@rel"/><xsl:copy-of select="@target"/>'
            // The label is a different thing from a bare link, so it says so
            // rather than inheriting link styling meant for running text.
            .'<xsl:attribute name="class"><xsl:value-of select="@class"/> UrlLink--discussion</xsl:attribute>'
            .'<xsl:if test="@faviconurl">'
                // Decorative: the text beside it already says where the link
                // goes, so a screen reader announcing the icon as well would
                // only repeat it.
                .'<img src="{@faviconurl}" class="UrlLink-favicon" alt="" aria-hidden="true"/>'
            .'</xsl:if>'
            .'<span class="UrlLink-discussion">#<xsl:value-of select="@discussionid"/></span>'
            .'<xsl:if test="@postnumber">'
                .'<span class="UrlLink-post">'
                    .'<i class="icon fas fa-comment UrlLink-postIcon" aria-hidden="true"></i>'
                    .'<xsl:value-of select="@postnumber"/>'
                .'</span>'
            .'</xsl:if>'
            .'</a>';
    }

    /**
     * Get a TextFormatter component ("renderer" or "parser" or "js").
     */
    protected function getComponent(string $name): mixed
    {
        $formatter = $this->cache->rememberForever('flarum.formatter', function () {
            return $this->getConfigurator()->finalize();
        });

        return $formatter[$name];
    }

    protected function getParser(mixed $context = null): Parser
    {
        $parser = $this->getComponent('parser');

        $parser->registeredVars['context'] = $context;

        return $parser;
    }

    protected function getRenderer(): Renderer
    {
        spl_autoload_register(function ($class) {
            if (file_exists($file = $this->cacheDir.'/'.$class.'.php')) {
                include $file;
            }
        });

        return $this->getComponent('renderer');
    }

    /**
     * Get the formatter JavaScript.
     */
    public function getJs(): string
    {
        return $this->getComponent('js');
    }

    /**
     * Decide how each link in a post should be treated, based on where it goes.
     *
     * Only fills in what has not already been set, so an extension rendering
     * callback — which runs before this — keeps the last word on any link.
     */
    protected function configureDefaultsOnLinks(string $xml): string
    {
        return Utils::replaceAttributes($xml, 'URL', function ($attributes) {
            // The stored XML names this `url`; `href` only exists once the
            // template has rendered, which has not happened yet.
            if ($this->isInternalUrl($url = ($attributes['url'] ?? ''))) {
                // Our own content. `noopener` still applies — trusting where a
                // link goes says nothing about handing over the opener window
                // — but `nofollow` and `ugc` would tell search engines to
                // ignore a forum linking to itself.
                $attributes['rel'] ??= 'noopener';
                $attributes['target'] ??= '_self';
                $attributes['class'] = trim(($attributes['class'] ?? '').' UrlLink UrlLink--internal');

                if ($discussion = $this->parseDiscussionUrl($url)) {
                    // The template shows these instead of the address when they
                    // are present. Set as attributes rather than by rewriting
                    // the link's text, so that a theme can lay the pieces out
                    // however it likes.
                    $attributes['discussionid'] = $discussion['id'];

                    if ($discussion['near'] !== null) {
                        $attributes['postnumber'] = $discussion['near'];
                    }

                    if ($favicon = $this->getFaviconUrl()) {
                        $attributes['faviconurl'] = $favicon;
                    }
                }
            } else {
                $attributes['rel'] ??= 'ugc nofollow';
            }

            return $attributes;
        });
    }

    /**
     * Pick out the discussion a URL points at, if it points at one.
     *
     * Matches the shape the discussion route declares: an id, optionally
     * followed by a slug that carries no meaning here, and optionally a post
     * number after it.
     *
     * @return array{id: string, near: string|null}|null
     */
    protected function parseDiscussionUrl(string $url): ?array
    {
        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path)) {
            return null;
        }

        $base = $this->getForumOrigin()['path'];

        if ($base !== '') {
            $path = substr($path, strlen($base));
        }

        if (! preg_match('~^/d/(\d+)(?:-[^/]*)?(?:/([^/]*))?/?$~', $path, $matches)) {
            return null;
        }

        // `/d/1/near-something` and other non-numeric positions cannot be
        // labelled, so the link keeps its address rather than claiming a post
        // number it did not find.
        $near = ($matches[2] ?? '') !== '' ? $matches[2] : null;

        if ($near !== null && ! ctype_digit($near)) {
            return null;
        }

        return ['id' => $matches[1], 'near' => $near];
    }

    /**
     * The forum's favicon, if one has been uploaded.
     *
     * Looked up once per request for the same reason as the forum's address:
     * this is consulted for every discussion link on the page.
     */
    protected function getFaviconUrl(): ?string
    {
        if ($this->faviconUrl === false) {
            $path = resolve(SettingsRepositoryInterface::class)->get('favicon_path');

            // Resolved through the assets filesystem rather than assembled by
            // hand, so a forum serving its uploads from a CDN gets the CDN
            // address here too.
            if ($path) {
                /** @var Cloud $assets */
                $assets = resolve(Factory::class)->disk('flarum-assets');

                $this->faviconUrl = $assets->url($path);
            } else {
                $this->faviconUrl = null;
            }
        }

        return $this->faviconUrl;
    }

    /**
     * The forum's host and base path.
     *
     * `Config::url()` builds a fresh Uri on every call, and this is consulted
     * once per link in every post on the page, so the answer is kept rather
     * than rebuilt. It cannot change within a request.
     *
     * @return array{host: string, path: string}
     */
    protected function getForumOrigin(): array
    {
        if ($this->forumOrigin === null) {
            $config = $this->config ?? resolve(Config::class);
            $url = $config->url();

            $this->forumOrigin = [
                'host' => $url->getHost(),
                'path' => rtrim($url->getPath(), '/'),
            ];
        }

        return $this->forumOrigin;
    }

    /**
     * Does this URL point back at the forum itself?
     *
     * Host and path both have to match: a forum installed at example.com/forum
     * does not own example.com/shop. The scheme is ignored, since a forum
     * reachable over both http and https is still one forum, and a link is not
     * a different destination for having been copied before the certificate
     * was installed.
     */
    protected function isInternalUrl(string $url): bool
    {
        // A scheme with no host — `mailto:`, `tel:`, `data:` — does not address
        // a place on this or any other site.
        if (preg_match('~^[a-z][a-z0-9+.-]*:~i', $url) && ! preg_match('~^https?://~i', $url)) {
            return false;
        }

        // `//evil.com/x` has no scheme but is absolute, and would otherwise be
        // read as the path `//evil.com/x` on this forum.
        if (str_starts_with($url, '//')) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (is_string($host) && $host !== '') {
            if (strcasecmp($host, $this->getForumOrigin()['host']) !== 0) {
                return false;
            }
        } elseif (! str_starts_with($url, '/')) {
            // A path-relative link like `d/1` resolves against whatever page it
            // happens to be read on, which is not knowable while rendering.
            return false;
        }

        // Either it named this forum's host, or it is a root-relative path on
        // whatever host the reader is using — which is this forum.
        $base = $this->getForumOrigin()['path'];

        if ($base === '') {
            return true;
        }

        $path = parse_url($url, PHP_URL_PATH) ?: '/';

        // `/forum` and `/forum/d/1` are inside the install; `/forums` is not,
        // so the base has to be followed by a boundary rather than just
        // appearing at the start.
        return $path === $base || str_starts_with($path, $base.'/');
    }

    /**
     * Converts a plain text string (with or without Markdown) to it's HTML equivalent.
     */
    public function convert(?string $content): string
    {
        if (! $content) {
            return '';
        }

        return $this->getRenderer()->render($this->getParser()->parse($content));
    }
}
