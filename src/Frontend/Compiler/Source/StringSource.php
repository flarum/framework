<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend\Compiler\Source;

class StringSource implements SourceInterface
{
    /**
     * @var callable
     */
    protected $callback;

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
        return call_user_func($this->callback);
    }

    /**
     * @return mixed
     */
    public function getCacheDifferentiator()
    {
        return $this->getContent();
    }
}
