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

use Flarum\Install\Prerequisite\PrerequisiteInterface;
use Flarum\Http\Controller\AbstractHtmlController;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Contracts\View\Factory;

class IndexController extends AbstractHtmlController
{
    /**
     * @var Factory
     */
    protected $view;

    /**
     * @var \Flarum\Install\Prerequisite\PrerequisiteInterface
     */
    protected $prerequisite;

    /**
     * @param Factory $view
     * @param PrerequisiteInterface $prerequisite
     */
    public function __construct(Factory $view, PrerequisiteInterface $prerequisite)
    {
        $this->view = $view;
        $this->prerequisite = $prerequisite;
    }

    /**
     * @param Request $request
     * @param array $routeParams
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function render(Request $request, array $routeParams = [])
    {
        $view = $this->view->make('flarum.install::app');

        $this->prerequisite->check();
        $errors = $this->prerequisite->getErrors();

        if (count($errors)) {
            $view->content = $this->view->make('flarum.install::errors');
            $view->content->errors = $errors;
        } else {
            $view->content = $this->view->make('flarum.install::install');
        }

        return $view;
    }
}
