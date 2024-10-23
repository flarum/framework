<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler\Source;

use Closure;

/**
 * @internal
 */
class StringSource implements SourceInterface
{
    private ?string $content = null;

    public function __construct(
        protected Closure $callback
    ) {
    }

    public function getContent(): string
    {
        return $this->content ??= call_user_func($this->callback);
    }

    public function getCacheDifferentiator(): string
    {
        return $this->getContent();
    }
}
