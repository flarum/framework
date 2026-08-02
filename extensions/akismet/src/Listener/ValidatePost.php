<?php

/*
 * This file is part of Flarum.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace Flarum\Akismet\Listener;

use Carbon\Carbon;
use Flarum\Akismet\Akismet;
use Flarum\Akismet\AkismetUnexpectedResponseException;
use Flarum\Flags\Flag;
use Flarum\Http\UrlGenerator;
use Flarum\Post\CommentPost;
use Flarum\Post\Event\Saving;
use Flarum\Settings\SettingsRepositoryInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

class ValidatePost
{
    public function __construct(
        protected Akismet $akismet,
        protected SettingsRepositoryInterface $settings,
        protected UrlGenerator $url,
        protected LoggerInterface $log
    ) {
    }

    public function handle(Saving $event): void
    {
        if (! $this->akismet->isConfigured()) {
            return;
        }

        $post = $event->post;

        if (! ($post instanceof CommentPost) || $post->user?->hasPermission('bypassAkismet')) {
            return;
        }

        // Spam is also introduced by editing an innocuous post after the
        // initial check passed, so edited content is rechecked.
        $isEdit = $post->exists;

        if ($isEdit && ! $post->isDirty('content')) {
            return;
        }

        try {
            $result = $this->buildRequest($post, $isEdit)->checkSpam();
        } catch (GuzzleException|AkismetUnexpectedResponseException $e) {
            // Fail open: an unreachable or misconfigured Akismet must never
            // block posting. The log line is the only place a bad key
            // surfaces, so make it identifiable.
            $this->log->warning("[flarum/akismet] Spam check failed, allowing the post through: {$e->getMessage()}");

            return;
        }

        if (! $result['isSpam']) {
            return;
        }

        $post->is_spam = true;

        if ($result['proTip'] === 'discard' && $this->settings->get('flarum-akismet.delete_blatant_spam')) {
            $post->hide();

            $post->afterSave(function ($post) {
                if ($post->number == 1) {
                    $post->discussion->hide();
                }
            });
        } else {
            $post->is_approved = false;

            $post->afterSave(function ($post) {
                if ($post->number == 1) {
                    $post->discussion->is_approved = false;
                    $post->discussion->save();
                }

                $flag = new Flag;

                $flag->post_id = $post->id;
                $flag->type = 'akismet';
                $flag->created_at = Carbon::now();

                $flag->save();
            });
        }
    }

    /**
     * Assemble the comment-check request. Akismet's docs are explicit that
     * detection accuracy "can drop dramatically" when data points are
     * omitted, so send everything we can know at this point.
     */
    protected function buildRequest(CommentPost $post, bool $isEdit): Akismet
    {
        $discussion = $post->discussion;

        // For new posts the number isn't assigned yet: the post starting a
        // discussion is the one whose discussion has no first post on record.
        $isFirstPost = $isEdit
            ? $post->number == 1
            : $discussion === null || ! $discussion->exists || $discussion->first_post_id === null;

        // Spam often lives in the discussion title; Akismet has no separate
        // title field, so fold it into the content for first posts.
        $content = $isFirstPost && $discussion !== null && $discussion->title
            ? $discussion->title."\n\n".$post->content
            : $post->content;

        $akismet = $this->akismet
            ->withContent($content)
            ->withAuthorName($post->user->username)
            ->withAuthorEmail($post->user->email)
            ->withType($isFirstPost ? 'forum-post' : 'reply')
            ->withCharset('UTF-8');

        if ($post->ip_address) {
            $akismet = $akismet->withIp($post->ip_address);
        }

        // Posts can also be created outside a browser request (console,
        // queue, integrations) — only pass request headers that exist.
        if ($userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null) {
            $akismet = $akismet->withUserAgent($userAgent);
        }

        if ($referrer = $_SERVER['HTTP_REFERER'] ?? null) {
            $akismet = $akismet->withReferrer($referrer);
        }

        if ($discussion !== null && $discussion->exists) {
            $akismet = $akismet->withPermalink(
                $this->url->to('forum')->route('discussion', ['id' => $discussion->id])
            );
        }

        if ($locale = $this->settings->get('default_locale')) {
            $akismet = $akismet->withLanguage($locale);
        }

        if ($isEdit) {
            $akismet = $akismet->withRecheckReason('edit');
        }

        return $akismet;
    }
}
