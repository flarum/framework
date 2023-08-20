<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Forum\Controller;

use Carbon\Carbon;
use Flarum\Http\Controller\AbstractHtmlController;
use Flarum\Http\UrlGenerator;
use Flarum\Locale\TranslatorInterface;
use Flarum\Notification\UnsubscribeToken;
use Flarum\Settings\SettingsRepositoryInterface;
use Flarum\User\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Psr\Http\Message\ServerRequestInterface as Request;

class UnsubscribeController extends AbstractHtmlController
{
    public function __construct(
        protected UrlGenerator $url,
        protected Factory $view,
        protected TranslatorInterface $translator,
        protected SettingsRepositoryInterface $settings
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

        $settingsLink = $this->url->to('forum')->route('settings');
        $forumTitle = $this->settings->get('forum_title');

        // If record exists and has not been used before
        if ($unsubscribeRecord && empty($unsubscribeRecord->unsubscribed_at)) {
            // Mark as unsubscribed
            $unsubscribeRecord->unsubscribed_at = Carbon::now();
            $unsubscribeRecord->save();

            // Update user preferences
            /** @var User $user */
            $user = User::find($userId);
            $user->setNotificationPreference($unsubscribeRecord->email_type, 'email', false);
            $user->save();

            $message = $this->translator->trans('core.views.unsubscribe_email.success_message', [
                'settingsLink' => $settingsLink,
                'forumTitle' => $forumTitle
            ]);
        } else {
            // If the token doesn't exist or has already been used
            $message = $this->translator->trans('core.views.unsubscribe_email.invalid_message', [
                'settingsLink' => $settingsLink,
                'forumTitle' => $forumTitle
            ]);
        }

        return $this->view->make('flarum.forum::unsubscribe-confirmation')->with('message', $message);
    }
}
