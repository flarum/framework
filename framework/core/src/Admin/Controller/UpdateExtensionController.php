<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Admin\Controller;

use Flarum\Bus\Dispatcher;
use Flarum\Extension\Command\ToggleExtension;
use Flarum\Http\Controller\AbstractController;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UpdateExtensionController extends AbstractController
{
    public function __construct(
        protected UrlGenerator $url,
        protected Dispatcher $bus
    ) {
    }

    public function __invoke(Request $request, string $name): RedirectResponse
    {
        $actor = RequestUtil::getActor($request);
        $enabled = (bool) (int) $request->json('enabled');

        $this->bus->dispatch(
            new ToggleExtension($actor, $name, $enabled)
        );

        return new RedirectResponse($this->url->base('admin'));
    }
}
