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

        $errors = [];

        if (version_compare(PHP_VERSION, '5.5.0', '<')) {
            $errors[] = [
                'message' => '<strong>PHP 5.5+</strong> is required.',
                'detail' => 'You are running version '.PHP_VERSION.'.'
            ];
        }

        foreach (['mbstring', 'pdo_mysql', 'json', 'gd'] as $extension) {
            if (! extension_loaded($extension)) {
                $errors[] = [
                    'message' => 'The <strong>'.$extension.'</strong> extension is required.'
                ];
            }
        }

        if (count($errors)) {
            $view->content = $this->view->make('flarum.install::errors');
            $view->content->errors = $errors;
        } else {
            $view->content = $this->view->make('flarum.install::install');
        }

        return $view;
    }
}
