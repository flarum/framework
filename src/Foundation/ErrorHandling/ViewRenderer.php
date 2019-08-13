<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Foundation\ErrorHandling;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Symfony\Component\Translation\TranslatorInterface;
use Zend\Diactoros\Response\HtmlResponse;

class ViewRenderer implements Formatter
{
    /**
     * @var ViewFactory
     */
    protected $view;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    public function __construct(ViewFactory $view, TranslatorInterface $translator, SettingsRepositoryInterface $settings)
    {
        $this->view = $view;
        $this->translator = $translator;
        $this->settings = $settings;
    }

    public function format(HandledError $error, Request $request): Response
    {
        $view = $this->view->make($this->determineView($error))
            ->with('error', $error->getError())
            ->with('message', $this->getMessage($error));

        return new HtmlResponse($view->render(), $error->getStatusCode());
    }

    private function determineView(HandledError $error): string
    {
        $view = [
            'route_not_found' => '404',
            'csrf_token_mismatch' => '419',
        ][$error->getType()] ?? 'default';

        return "flarum.forum::error.$view";
    }

    private function getMessage(HandledError $error)
    {
        return $this->getTranslationIfExists($error->getStatusCode())
            ?? $this->getTranslationIfExists('unknown')
            ?? 'An error occurred while trying to load this page.';
    }

    private function getTranslationIfExists(string $errorType)
    {
        $key = "core.views.error.${errorType}_message";
        $translation = $this->translator->trans($key, ['{forum}' => $this->settings->get('forum_title')]);

        return $translation === $key ? null : $translation;
    }
}
