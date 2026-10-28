<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Mail\MailWebhooks;
use Flarum\Settings\SettingsRepositoryInterface;
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
        protected MailWebhooks $webhooks,
        protected SettingsRepositoryInterface $settings,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertAdmin();

        $driver = $this->webhooks->provisioner();

        if (! $driver) {
            return $this->error('The active mail driver cannot register webhooks.');
        }

        $webhookUrl = $this->webhooks->url();

        $unregister = (bool) Arr::get($request->getParsedBody(), 'unregister', false);

        try {
            if ($unregister) {
                $driver->unregisterWebhook($this->settings, $webhookUrl);
            } else {
                $driver->registerWebhook($this->settings, $webhookUrl);
            }
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }

        return new JsonResponse([
            'registered' => ! $unregister,
            'url' => $webhookUrl,
        ]);
    }

    protected function error(string $detail): ResponseInterface
    {
        return new JsonResponse([
            'errors' => [
                ['status' => '422', 'code' => 'mail_webhook_not_registered', 'detail' => $detail],
            ],
        ], 422);
    }
}
