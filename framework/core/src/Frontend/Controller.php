<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Http\Controller\AbstractController;
use Illuminate\Http\Request;

class Controller extends AbstractController
{
    public function __construct(
        protected Frontend $frontend
    ) {
    }

    public function __invoke(Request $request): string
    {
        return $this->frontend->document($request)->render();
    }
}
