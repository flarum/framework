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
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

class UpdateExtensionController implements RequestHandlerInterface
{
    public function __construct(
        protected UrlGenerator $url,
        protected Dispatcher $bus
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $enabled = (bool) (int) Arr::get($request->getParsedBody(), 'enabled');
        $name = Arr::get($request->getQueryParams(), 'name');

        $this->bus->dispatch(
            new ToggleExtension($actor, $name, $enabled)
        );

        return new RedirectResponse($this->url->to('admin')->base());
    }
}
