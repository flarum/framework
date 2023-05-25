<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Update\Controller;

use Flarum\Http\Controller\AbstractHtmlController;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ServerRequestInterface as Request;

class IndexController extends AbstractHtmlController
{
    public function __construct(
        protected Factory $view
    ) {
    }

    public function render(Request $request): Renderable|string
    {
        $view = $this->view->make('flarum.update::app')->with('title', 'Update Flarum');

        $view->with('content', $this->view->make('flarum.update::update'));

        return $view;
    }
}
