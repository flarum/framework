<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\PHPStan\Extender;

class MethodCall
{
    /** @var string */
    public $methodName;
    /** @var array */
    public $arguments;

    public function __construct(string $methodName, array $arguments = [])
    {
        $this->methodName = $methodName;
        $this->arguments = $arguments;
    }
}
