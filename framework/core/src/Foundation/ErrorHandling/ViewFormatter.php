<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling;

use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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
class ViewFormatter implements HttpFormatter
{
    const ERRORS_WITH_VIEWS = ['csrf_token_mismatch', 'not_found', 'maintenance'];

    public function __construct(
        protected ViewFactory $view,
        protected TranslatorInterface $translator,
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function format(HandledError $error, Request $request): Response
    {
        $view = $this->view
            ->make($this->determineView($error))
            ->with('error', $error->getException())
            ->with('message', $this->getMessage($error));

        return new HtmlResponse($view->render(), $error->getStatusCode());
    }

    private function determineView(HandledError $error): string
    {
        $type = $error->getType();

        if (in_array($type, self::ERRORS_WITH_VIEWS)) {
            return "flarum.forum::error.$type";
        } else {
            return 'flarum.forum::error.default';
        }
    }

    private function getMessage(HandledError $error): string
    {
        return $this->getTranslationIfExists($error->getType())
            ?? $this->getTranslationIfExists('unknown')
            ?? 'An error occurred while trying to load this page.';
    }

    private function getTranslationIfExists(string $errorType): ?string
    {
        $key = "core.views.error.$errorType";
        $translation = $this->translator->trans($key, ['forum' => $this->settings->get('forum_title')]);

        return $translation === $key ? null : $translation;
    }
}
