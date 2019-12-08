<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Event;

use Zend\Stratigility\MiddlewarePipe;

/**
 * @deprecated
 */
class ConfigureMiddleware
{
    /**
     * @var MiddlewarePipe
     */
    public $pipe;

    /**
     * @var string
     */
    public $stackName;

    /**
     * @param MiddlewarePipe $pipe
     * @param string $stackName
     */
    public function __construct(MiddlewarePipe $pipe, $stackName)
    {
        $this->pipe = $pipe;
        $this->stackName = $stackName;
    }

    public function pipe($middleware)
    {
        $this->pipe->pipe($middleware);
    }

    public function isForum()
    {
        return $this->stackName === 'forum';
    }

    public function isAdmin()
    {
        return $this->stackName === 'admin';
    }

    public function isApi()
    {
        return $this->stackName === 'api';
    }
}
