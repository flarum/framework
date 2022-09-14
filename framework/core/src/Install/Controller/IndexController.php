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
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ServerRequestInterface as Request;

class IndexController extends AbstractHtmlController
{
    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var Installation
     */
    protected $installation;

    public function __construct(Factory $view, Installation $installation)
    {
        $this->view = $view;
        $this->installation = $installation;
    }

    /**
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function render(Request $request)
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
