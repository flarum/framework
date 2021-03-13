<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Api\Controller;

use Flarum\Http\UrlGenerator;
use Flarum\Mail\Job\SendRawEmailJob;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\EmailToken;
use Flarum\User\Exception\PermissionDeniedException;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SendConfirmationEmailController implements RequestHandlerInterface
{
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var UrlGenerator
     */
    protected $url;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param \Flarum\Settings\SettingsRepositoryInterface $settings
     * @param Queue $queue
     * @param UrlGenerator $url
     * @param TranslatorInterface $translator
     */
    public function __construct(SettingsRepositoryInterface $settings, Queue $queue, UrlGenerator $url, TranslatorInterface $translator)
    {
        $this->settings = $settings;
        $this->queue = $queue;
        $this->url = $url;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = Arr::get($request->getQueryParams(), 'id');
        $actor = $request->getAttribute('actor');

        $actor->assertRegistered();

        if ($actor->id != $id || $actor->is_email_confirmed) {
            throw new PermissionDeniedException;
        }

        $token = EmailToken::generate($actor->email, $actor->id);
        $token->save();

        $data = [
            '{username}' => $actor->username,
            '{url}' => $this->url->to('forum')->route('confirmEmail', ['token' => $token->token]),
            '{forum}' => $this->settings->get('forum_title')
        ];

        $body = $this->translator->trans('core.email.activate_account.body', $data);
        $subject = $this->translator->trans('core.email.activate_account.subject');

        $this->queue->push(new SendRawEmailJob($actor->email, $subject, $body));

        return new EmptyResponse;
    }
}
