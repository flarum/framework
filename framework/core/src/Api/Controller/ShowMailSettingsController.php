<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Api\JsonApi;
use Flarum\Api\Resource\MailSettingResource;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ShowMailSettingsController implements RequestHandlerInterface
{
    public function __construct(
        protected JsonApi $api
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->api
            ->forResource(MailSettingResource::class)
            ->forEndpoint('show')
            ->handle($request);
    }
}
