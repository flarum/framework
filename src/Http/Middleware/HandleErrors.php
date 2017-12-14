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

use Exception;
use Flarum\Settings\SettingsRepositoryInterface;
use Franzl\Middleware\Whoops\ErrorMiddleware as WhoopsMiddleware;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Zend\Diactoros\Response\HtmlResponse;

class HandleErrors
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
     * @var bool
     */
    protected $debug;

    /**
     * @param ViewFactory $view
     * @param LoggerInterface $logger
     * @param TranslatorInterface $translator
     * @param SettingsRepositoryInterface $settings
     * @param bool $debug
     */
    public function __construct(ViewFactory $view, LoggerInterface $logger, TranslatorInterface $translator, SettingsRepositoryInterface $settings, $debug = false)
    {
        $this->view = $view;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->settings = $settings;
        $this->debug = $debug;
    }

    /**
     * Catch all errors that happen during further middleware execution.
     *
     * @param Request $request
     * @param Response $response
     * @param callable $out
     * @return Response
     */
    public function __invoke(Request $request, Response $response, callable $out = null)
    {
        try {
            return $out($request, $response);
        } catch (Exception $e) {
            return $this->formatException($e, $request, $response, $out);
        }
    }

    protected function formatException(Exception $error, Request $request, Response $response, callable $out = null)
    {
        $status = 500;
        $errorCode = $error->getCode();

        // If it seems to be a valid HTTP status code, we pass on the
        // exception's status.
        if (is_int($errorCode) && $errorCode >= 400 && $errorCode < 600) {
            $status = $errorCode;
        }

        if ($this->debug) {
            $whoops = new WhoopsMiddleware;

            return $whoops($error, $request, $response, $out);
        }

        // Log the exception (with trace)
        $this->logger->debug($error);

        if (! $this->view->exists($name = 'flarum.forum::error.'.$status)) {
            $name = 'flarum.forum::error.default';
        }

        $view = $this->view->make($name)
            ->with('error', $error)
            ->with('message', $this->getMessage($status));

        return new HtmlResponse($view->render(), $status);
    }

    private function getMessage($status)
    {
        if (! $translation = $this->getTranslationIfExists($status)) {
            if (! $translation = $this->getTranslationIfExists(500)) {
                $translation = 'An error occurred while trying to load this page.';
            }
        }

        return $translation;
    }

    private function getTranslationIfExists($status)
    {
        $key = 'core.views.error.'.$status.'_message';
        $translation = $this->translator->trans($key, ['{forum}' => $this->settings->get('forum_title')]);

        return $translation === $key ? false : $translation;
    }
}
