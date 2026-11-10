<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Update\Controller;

use Flarum\Foundation\Config;
use Flarum\Http\Controller\AbstractHtmlController;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;

class IndexController extends AbstractHtmlController
{
    public function __construct(
        protected Factory $view,
        protected Config $config
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $view = $this->render($request);

        if ($view instanceof Renderable) {
            $view = $view->render();
        }

        return new HtmlResponse($view, 503);
    }

    public function render(Request $request): Renderable|string
    {
        $view = $this->view->make('flarum.update::app')->with('title', 'Update Flarum');

        // SQLite has no username or password, so the updater verifies against
        // the database file's name instead. Only then does the form need that
        // field — the same condition the verification uses, kept in step so the
        // form never asks for something the check ignores, or omits something
        // it requires.
        $usesDatabaseName = (string) $this->config['database.username'] === ''
            && (string) $this->config['database.password'] === '';

        $view->with('content', $this->view->make('flarum.update::update')->with(
            'usesDatabaseName',
            $usesDatabaseName
        ));

        return $view;
    }
}
