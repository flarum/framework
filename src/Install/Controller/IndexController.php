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
use Flarum\Install\Database\Drivers;
use Flarum\Install\Prerequisite\PrerequisiteInterface;
use Illuminate\Contracts\View\Factory;
use Psr\Http\Message\ServerRequestInterface as Request;

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
     * @param Drivers $drivers
     */
    public function __construct(Factory $view, PrerequisiteInterface $prerequisite, Drivers $drivers)
    {
        $this->view = $view;
        $this->prerequisite = $prerequisite;
        $this->drivers = $drivers;
    }

    /**
     * @param Request $request
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function render(Request $request)
    {
        $view = $this->view->make('flarum.install::app')->with('title', 'Install Flarum');

        $this->prerequisite->check();
        $errors = $this->prerequisite->getErrors();

        if (count($errors)) {
            $view->with('content', $this->view->make('flarum.install::errors')->with('errors', $errors));
        } else {
            $view->with('content', $this->view->make('flarum.install::install')->with('drivers', $this->drivers->getLoadedDatabaseDrivers()));
        }

        return $view;
    }
}
