<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Install\Controller;

use Flarum\Http\Controller\AbstractHtmlController;
use Flarum\Install\Installation;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ServerRequestInterface as Request;

class IndexController extends AbstractHtmlController
{
    public function __construct(
        protected Factory $view,
        protected Installation $installation
    ) {
    }

    public function render(Request $request): Renderable|string
    {
        $view = $this->view->make('flarum.install::app')->with('title', 'Install Flarum');

        $problems = $this->installation->prerequisites()->problems();

        if ($problems->isEmpty()) {
            $view->with('content', $this->view->make('flarum.install::install'));
        } else {
            $view->with('content', $this->view->make('flarum.install::problems')->with('problems', $problems));
        }

        return $view;
    }
}
