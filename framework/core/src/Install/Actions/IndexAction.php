<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Install\Actions;

use Flarum\Support\HtmlAction;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Contracts\View\Factory;

class IndexAction extends HtmlAction
{
    /**
     * @var Factory
     */
    protected $view;

    /**
     * @param Factory $view
     */
    public function __construct(Factory $view)
    {
        $this->view = $view;
    }

    /**
     * @param Request $request
     * @param array $routeParams
     * @return \Psr\Http\Message\ResponseInterface|EmptyResponse
     */
    public function render(Request $request, array $routeParams = [])
    {
        $view = $this->view->make('flarum.install::app');

        $view->logo = $this->view->make('flarum.install::logo');

        $view->content = $this->view->make('flarum.install::install');
        $view->content->input = [];

        return $view;
    }
}
