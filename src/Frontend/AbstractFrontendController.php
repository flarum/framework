<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Frontend;

use Flarum\Event\ConfigureClientView;
use Flarum\Frontend\Event\Rendering;
use Flarum\Http\Controller\AbstractHtmlController;
use Illuminate\Contracts\Events\Dispatcher;
use Psr\Http\Message\ServerRequestInterface as Request;

abstract class AbstractFrontendController extends AbstractHtmlController
{
    /**
     * @var AbstractFrontend
     */
    protected $webApp;

    /**
     * @var Dispatcher
     */
    protected $events;

    /**
     * {@inheritdoc}
     */
    public function render(Request $request)
    {
        $view = $this->getView($request);

        $this->events->dispatch(
            new ConfigureClientView($this, $view, $request)
        );
        $this->events->dispatch(
            new Rendering($this, $view, $request)
        );

        return $view->render($request);
    }

    /**
     * @param Request $request
     * @return \Flarum\Frontend\FrontendView
     */
    protected function getView(Request $request)
    {
        return $this->webApp->getView();
    }
}
