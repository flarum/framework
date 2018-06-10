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

use Flarum\Http\Controller\ControllerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Zend\Diactoros\Response\HtmlResponse;

class FrontendController implements ControllerInterface
{
    /**
     * @var FrontendViewFactory
     */
    protected $view;

    /**
     * @param FrontendViewFactory $view
     */
    public function __construct(FrontendViewFactory $view)
    {
        $this->view = $view;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request)
    {
        return new HtmlResponse(
            $this->view->make($request)->render()
        );
    }
}
