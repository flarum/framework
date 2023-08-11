<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Extension\Command\ToggleExtension;
use Flarum\Http\Controller\AbstractController;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;

class UpdateExtensionController extends AbstractController
{
    public function __construct(
        protected Dispatcher $bus
    ) {
    }

    public function __invoke(Request $request, string $name): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $enabled = (bool) (int) $request->json('enabled');

        $this->bus->dispatch(
            new ToggleExtension($actor, $name, $enabled)
        );

        return new EmptyResponse;
    }
}
