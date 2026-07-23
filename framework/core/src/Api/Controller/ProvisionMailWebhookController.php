<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Flarum\Mail\ProvisionsWebhooks;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Registers or unregisters the delivery-event webhook with the active mail
 * driver's provider, so the admin never has to visit the provider dashboard.
 */
class ProvisionMailWebhookController implements RequestHandlerInterface
{
    public function __construct(
        protected Container $container,
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $url,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $driver = $this->container->make('flarum.mail.configured_driver');

        if (! $driver instanceof ProvisionsWebhooks) {
            return new JsonResponse([
                'error' => 'The active mail driver does not support webhook provisioning.',
            ], 422);
        }

        $webhookUrl = $this->url->to('forum')->route('mailWebhook', [
            'driver' => $this->settings->get('mail_driver'),
        ]);

        $unregister = (bool) Arr::get($request->getParsedBody(), 'unregister', false);

        try {
            if ($unregister) {
                $driver->unregisterWebhook($this->settings, $webhookUrl);
            } else {
                $driver->registerWebhook($this->settings, $webhookUrl);
            }
        } catch (\Throwable $e) {
            return new JsonResponse([
                'error' => $e->getMessage(),
            ], 502);
        }

        return new JsonResponse([
            'registered' => ! $unregister,
            'url' => $webhookUrl,
        ]);
    }
}
