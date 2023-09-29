<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\RequestUtil;
use Flarum\Locale\TranslatorInterface;
use Flarum\Mail\Job\SendInformationalEmailJob;
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Queue\Factory;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SendTestMailController implements RequestHandlerInterface
{
    public function __construct(
        protected Mailer $mailer,
        protected TranslatorInterface $translator,
        protected SettingsRepositoryInterface $settings,
        protected Factory $queue
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        $this->queue->connection('sync')->push(
            new SendInformationalEmailJob(
                email: $actor->email,
                displayName: $actor->display_name,
                subject: $this->translator->trans('core.email.send_test.subject'),
                body: $this->translator->trans('core.email.send_test.body'),
                forumTitle: $this->settings->get('forum_title'),
                bodyTitle: $this->translator->trans('core.email.send_test.subject')
            )
        );

        return new EmptyResponse();
    }
}
