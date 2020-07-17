<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Illuminate\Container\Container;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Message;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SendTestMailController implements RequestHandlerInterface
{
    protected $container;

    protected $mailer;

    protected $translator;

    public function __construct(Container $container, Mailer $mailer, TranslatorInterface $translator)
    {
        $this->container = $container;
        $this->mailer = $mailer;
        $this->translator = $translator;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = $request->getAttribute('actor');
        $actor->assertAdmin();

        $body = $this->translator->trans('core.email.send_test.body', ['{username}' => $actor->username]);

        $this->mailer->raw($body, function (Message $message) use ($actor) {
            $message->to($actor->email);
            $message->subject($this->translator->trans('core.email.send_test.subject'));
        });

        return new EmptyResponse();
    }
}
