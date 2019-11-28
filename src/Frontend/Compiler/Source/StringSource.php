<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler\Source;

class StringSource implements SourceInterface
{
    /**
     * @var callable
     */
    protected $callback;

    private $content;

    /**
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        if (is_null($this->content)) {
            $this->content = call_user_func($this->callback);
        }

        return $this->content;
    }

    /**
     * @return mixed
     */
    public function getCacheDifferentiator()
    {
        return $this->getContent();
    }
}
