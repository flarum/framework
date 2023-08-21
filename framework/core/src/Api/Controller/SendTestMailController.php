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
use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SendTestMailController implements RequestHandlerInterface
{
    public function __construct(
        protected Mailer $mailer,
        protected TranslatorInterface $translator,
        protected SettingsRepositoryInterface $settings
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        $infoContent = $this->translator->trans('core.email.send_test.body', ['username' => $actor->username]);

        $title = $this->translator->trans('core.email.send_test.subject');
        $forumTitle = $this->settings->get('forum_title');
        $userEmail = $actor->email;

        $this->mailer->send(
            [
                'plain' => 'flarum.forum::email.information.plain.base',
                'html' => 'flarum.forum::email.information.html.base'
            ],
            compact('infoContent', 'userEmail', 'forumTitle', 'title'),
            function (Message $message) use ($actor) {
                $message->to($actor->email);
                $message->subject($this->translator->trans('core.email.send_test.subject'));
            }
        );

        return new EmptyResponse();
    }
}
