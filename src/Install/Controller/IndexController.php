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
use Flarum\Install\Prerequisite\PrerequisiteInterface;
use Flarum\Locale\LocaleManager;
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
     * @var LocaleManager
     */
    protected $locale;

    /**
     * @param Factory $view
     * @param PrerequisiteInterface $prerequisite
     * @param LocaleManager $locale
     */
    public function __construct(Factory $view, PrerequisiteInterface $prerequisite, LocaleManager $locale)
    {
        $this->view = $view;
        $this->prerequisite = $prerequisite;
        $this->locale = $locale;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function render(Request $request)
    {
        $view = $this->view->make('flarum.install::app')->with('title', 'Install Flarum');

        $this->prerequisite->check();
        $errors = $this->prerequisite->getErrors();

        if ($this->locale->getLoadedLocales() < 1) {
            array_push($errors, [
                'message' => 'No language pack installed.',
                'detail' => 'You have to install a language pack to continue with the installation.'
            ]);
        }

        if (count($errors)) {
            $view->with('content', $this->view->make('flarum.install::errors')->with('errors', $errors));
        } else {
            $view->with('content', $this->view->make('flarum.install::install'));
        }

        return $view;
    }
}
