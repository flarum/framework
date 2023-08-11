<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation;

use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Support\ServiceProvider;

abstract class AbstractServiceProvider extends ServiceProvider
{
    protected ApplicationContract $container;

    public function __construct(ApplicationContract $app)
    {
        parent::__construct($app);

        $this->container = $app;
    }
}
