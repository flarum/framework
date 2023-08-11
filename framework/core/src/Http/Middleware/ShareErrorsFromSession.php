<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Closure;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\Response;

/**
 * Inspired by Illuminate\View\Middleware\ShareErrorsFromSession.
 *
 * @author Taylor Otwell
 */
class ShareErrorsFromSession implements IlluminateMiddlewareInterface
{
    public function __construct(
        protected ViewFactory $view
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->attributes->get('session');

        // If the current session has an "errors" variable bound to it, we will share
        // its value with all view instances so the views can easily access errors
        // without having to bind. An empty bag is set when there aren't errors.
        $this->view->share(
            'errors',
            $session->get('errors', new ViewErrorBag)
        );

        // Putting the errors in the view for every view allows the developer to just
        // assume that some errors are always available, which is convenient since
        // they don't have to continually run checks for the presence of errors.

        $session->remove('errors');

        return $next($request);
    }
}
