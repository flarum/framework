<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Gdpr\Http\Controller;

use Flarum\Foundation\ValidationException;
use Flarum\Gdpr\Models\ErasureRequest;
use Flarum\Http\Controller\AbstractHtmlController;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * The page the emailed confirmation link opens. Mail security scanners open
 * links in incoming mail, so the link only asks: the request is confirmed by
 * submitting the form, handled by ConfirmErasureController.
 */
class ConfirmErasureViewController extends AbstractHtmlController
{
    public function __construct(
        protected Factory $view
    ) {
    }

    public function render(Request $request): View
    {
        $token = Arr::get($request->getQueryParams(), 'token');

        /** @var ErasureRequest $erasureRequest */
        $erasureRequest = ErasureRequest::query()
            ->where('verification_token', $token)
            ->firstOrFail();

        if (in_array($erasureRequest->status, [ErasureRequest::STATUS_PROCESSED, ErasureRequest::STATUS_MANUAL])) {
            throw new ValidationException(['request' => 'This erasure request has already been processed.']);
        }

        return $this->view
            ->make('flarum-gdpr::confirm-erasure')
            ->with('csrfToken', $request->getAttribute('session')->token())
            ->with('actorClass', RequestUtil::getActorClass($request));
    }
}
