<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling;

use Flarum\Frontend\Controller;
use Flarum\Http\Content\NotAuthenticated;
use Flarum\Http\Content\NotFound;
use Flarum\Http\Content\PermissionDenied;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * A formatter for turning caught exceptions into "pretty" HTML error pages.
 *
 * For certain known error types, we display pages with dedicated information
 * relevant to this class of error, e.g. a page with a search form for HTTP 404
 * "Not Found" errors. We look for templates in the `views/error` directory.
 *
 * If no specific template exists, a generic "Something went wrong" page will be
 * displayed, optionally enriched with a more specific error message if found in
 * the translation files.
 */
class FrontendFormatter implements HttpFormatter
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(Container $container, TranslatorInterface $translator, SettingsRepositoryInterface $settings)
    {
        $this->container = $container;
        $this->translator = $translator;
        $this->settings = $settings;
    }

    public function format(HandledError $error, Request $request): Response
    {
        $frontend = $this->container->make('flarum.frontend.forum');

        if ($error->getStatusCode() === 401) {
            $frontend->content(new NotAuthenticated);
        } elseif ($error->getStatusCode() === 403) {
            $frontend->content(new PermissionDenied);
        } elseif ($error->getStatusCode() === 404) {
            $frontend->content(new NotFound);
        }

        return (new Controller($frontend))->handle($request)->withStatus($error->getStatusCode());
    }
}
