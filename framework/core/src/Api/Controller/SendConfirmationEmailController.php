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
use Flarum\Locale\TranslatorInterface;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\AccountActivationMailerTrait;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SendConfirmationEmailController implements RequestHandlerInterface
{
    use AccountActivationMailerTrait;

    public function __construct(
        protected SettingsRepositoryInterface $settings,
        protected Queue $queue,
        protected UrlGenerator $url,
        protected TranslatorInterface $translator
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = Arr::get($request->getQueryParams(), 'id');
        $actor = RequestUtil::getActor($request);

        $actor->assertRegistered();

        if ($actor->id != $id || $actor->is_email_confirmed) {
            throw new PermissionDeniedException;
        }

        $token = $this->generateToken($actor, $actor->email);
        $data = $this->getEmailData($actor, $token);

        $this->sendConfirmationEmail($actor, $data);

        return new EmptyResponse;
    }
}
