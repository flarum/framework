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
        protected TranslatorInterface $translator
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertAdmin();

        $body = $this->translator->trans('core.email.send_test.body', ['username' => $actor->username]);

        $this->mailer->raw($body, function (Message $message) use ($actor) {
            $message->to($actor->email);
            $message->subject($this->translator->trans('core.email.send_test.subject'));
        });

        return new EmptyResponse();
    }
}
