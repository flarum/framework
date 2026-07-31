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
     * Anything else an email view might reach for goes to the real formatter.
     *
     * @param array<mixed> $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->formatter->$method(...$arguments);
    }
}
