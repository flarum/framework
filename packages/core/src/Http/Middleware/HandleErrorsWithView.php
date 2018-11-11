<?php

/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Http\Middleware;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Throwable;
use Zend\Diactoros\Response\HtmlResponse;

class HandleErrorsWithView implements Middleware
{
    /**
     * @var ViewFactory
     */
    protected $view;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @param ViewFactory $view
     * @param LoggerInterface $logger
     * @param TranslatorInterface $translator
     * @param SettingsRepositoryInterface $settings
     */
    public function __construct(ViewFactory $view, LoggerInterface $logger, TranslatorInterface $translator, SettingsRepositoryInterface $settings)
    {
        $this->view = $view;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->settings = $settings;
    }

    /**
     * Catch all errors that happen during further middleware execution.
     */
    public function process(Request $request, Handler $handler): Response
    {
        try {
            return $handler->handle($request);
        } catch (Throwable $e) {
            return $this->formatException($e);
        }
    }

    protected function formatException(Throwable $error)
    {
        $status = 500;
        $errorCode = $error->getCode();

        // If it seems to be a valid HTTP status code, we pass on the
        // exception's status.
        if (is_int($errorCode) && $errorCode >= 400 && $errorCode < 600) {
            $status = $errorCode;
        }

        if (! $this->view->exists($name = "flarum.forum::error.$status")) {
            $name = 'flarum.forum::error.default';

            $this->logger->error($error);
        }

        $view = $this->view->make($name)
            ->with('error', $error)
            ->with('message', $this->getMessage($status));

        return new HtmlResponse($view->render(), $status);
    }

    private function getMessage($status)
    {
        return $this->getTranslationIfExists($status)
            ?? $this->getTranslationIfExists(500)
            ?? 'An error occurred while trying to load this page.';
    }

    private function getTranslationIfExists($status)
    {
        $key = "core.views.error.${status}_message";
        $translation = $this->translator->trans($key, ['{forum}' => $this->settings->get('forum_title')]);

        return $translation === $key ? null : $translation;
    }
}
