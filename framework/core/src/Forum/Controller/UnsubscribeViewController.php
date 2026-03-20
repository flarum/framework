<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Flarum\Http\Controller\AbstractHtmlController;
use Flarum\Http\SlugManager;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use Flarum\Notification\UnsubscribeToken;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class UnsubscribeViewController extends AbstractHtmlController
{
    public function __construct(
        protected Factory $view,
        protected UrlGenerator $url,
        protected TranslatorInterface $translator,
        protected SettingsRepositoryInterface $settings,
        protected SlugManager $slugManager,
    ) {
    }

    public function render(Request $request): View
    {
        $userId = Arr::get($request->getQueryParams(), 'userId');
        $token = Arr::get($request->getQueryParams(), 'token');

        // Fetch the unsubscribe token record
        /** @var UnsubscribeToken|null $unsubscribeRecord */
        $unsubscribeRecord = UnsubscribeToken::where('user_id', $userId)
            ->where('token', $token)
            ->first();

        $user = User::find($userId);
        $userSlug = $this->slugManager->forResource(User::class)->toSlug($user);

        $settingsLink = $this->url->to('forum')->route('user', ['username' => $userSlug, 'filter' => 'settings']);
        $forumTitle = $this->settings->get('forum_title');

        // If record exists and has not been used before
        if ($unsubscribeRecord && empty($unsubscribeRecord->unsubscribed_at)) {
            $view = 'flarum.forum::unsubscribe-confirmation';
            $message = $this->translator->trans('core.views.unsubscribe_email.confirm_message', [
                'settingsLink' => $settingsLink,
                'forumTitle' => $forumTitle,
                'type' => $unsubscribeRecord->email_type
            ]);
        } else {
            // If the token doesn't exist or has already been used
            $view = 'flarum.forum::unsubscribe-error';
            $message = $this->translator->trans('core.views.unsubscribe_email.invalid_message', [
                'settingsLink' => $settingsLink,
                'forumTitle' => $forumTitle
            ]);
        }

        return $this->view
            ->make($view)
            ->with('message', $message)
            ->with('userId', $userId)
            ->with('token', $token)
            ->with('csrfToken', $request->getAttribute('session')->token());
    }
}
