<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Formatter;

use Flarum\User\User;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A trait to add formatted content to a model.
 *
 * @property string $content
 * @property string $parsed_content
 */
trait HasFormattedContent
{
    protected static Formatter $formatter;

    public function getContentAttribute(string $value): string
    {
        return static::$formatter->unparse($value, $this);
    }

    public function getParsedContentAttribute(): string
    {
        return $this->attributes['content'];
    }

    public function setContentAttribute(string $value, ?User $actor = null): void
    {
        $this->attributes['content'] = $value ? static::$formatter->parse($value, $this, $actor ?? $this->user) : null;
    }

    public function setParsedContentAttribute(string $value): void
    {
        $this->attributes['content'] = $value;
    }

    /**
     * Get the content rendered as HTML.
     */
    public function formatContent(?ServerRequestInterface $request = null): string
    {
        return static::$formatter->render($this->attributes['content'], $this, $request);
    }

    public static function getFormatter(): Formatter
    {
        return static::$formatter;
    }

    public static function setFormatter(Formatter $formatter): void
    {
        static::$formatter = $formatter;
    }
}
