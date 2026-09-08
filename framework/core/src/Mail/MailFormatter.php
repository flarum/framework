<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Mail;

use Flarum\Formatter\Formatter;

/**
 * The formatter email views are given.
 *
 * Renders through the real formatter, then puts back the values
 * {@see MailTranslator} held out of the way, escaped. Content that never went
 * through the mail translator — a post's body via `formatContent()`, for
 * instance — is unaffected, since it carries no markers.
 *
 * Not a subclass: email views only ever call `convert()`, and wrapping avoids
 * inheriting the parser and renderer state the real formatter manages.
 */
class MailFormatter
{
    public function __construct(
        protected Formatter $formatter
    ) {
    }

    public function convert(?string $content): string
    {
        return SafeSubstitution::restore($this->formatter->convert($content));
    }

    /**
     * Turn plain text an email view was handed into HTML: escaped, with its
     * line breaks kept and its URLs made clickable.
     *
     * Informational emails (account activation, email confirmation, password
     * reset) do not build their body in a view. It arrives already translated,
     * so its parameters, a display name among them, are part of the string by
     * the time a template sees it, and no markers are left for
     * {@see SafeSubstitution} to put back. Rendering such a body with
     * `convert()` would therefore put those values in front of the parser,
     * which is the thing the mail translator exists to prevent. The content is
     * escaped instead, and only URLs are linked: the visible text of every link
     * produced here is the address it points at.
     */
    public function plainToHtml(?string $content): string
    {
        if (! $content) {
            return '';
        }

        $paragraphs = array_filter(
            preg_split('/\R{2,}/', trim($content)) ?: [],
            fn (string $paragraph) => trim($paragraph) !== ''
        );

        return implode("\n", array_map(
            fn (string $paragraph) => '<p>'.nl2br($this->linkUrls(trim($paragraph)), false).'</p>',
            $paragraphs
        ));
    }

    /**
     * Escape a run of plain text, wrapping every URL in it in an anchor.
     *
     * Escaping happens as the text is assembled rather than up front, so that
     * an address containing `&` is escaped once (for the attribute and for the
     * link text) instead of the entity being fed back through the matcher.
     */
    private function linkUrls(string $text): string
    {
        $html = '';
        $offset = 0;

        preg_match_all('~\bhttps?://[^\s<>"]+~', $text, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$match, $position]) {
            // Sentence punctuation after an address is not part of it.
            $url = rtrim($match, '.,:;!?');

            // Neither is a closing bracket that was never opened inside it,
            // as in "(https://example.com/page)".
            while (str_ends_with($url, ')') && substr_count($url, ')') > substr_count($url, '(')) {
                $url = substr($url, 0, -1);
            }

            $html .= $this->escape(substr($text, $offset, $position - $offset));
            $html .= '<a href="'.$this->escape($url).'">'.$this->escape($url).'</a>';
            $html .= $this->escape(substr($match, strlen($url)));

            $offset = $position + strlen($match);
        }

        return $html.$this->escape(substr($text, $offset));
    }

    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Anything else an email view might reach for goes to the real formatter.
     *
     * @param array<mixed> $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->formatter->$method(...$arguments);
    }
}
