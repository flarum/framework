<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

    /**
     * @param Factory $view
     * @param Installation $installation
     */
    public function __construct(Factory $view, Installation $installation)
    {
        $this->view = $view;
        $this->installation = $installation;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function render(Request $request)
    {
        $view = $this->view->make('flarum.install::app')->with('title', 'Install Flarum');

        $prerequisites = $this->installation->prerequisites();
        $prerequisites->check();
        $errors = $prerequisites->getErrors();

        if (count($errors)) {
            $view->with('content', $this->view->make('flarum.install::errors')->with('errors', $errors));
        } else {
            $view->with('content', $this->view->make('flarum.install::install'));
        }

        return $view;
    }
}
