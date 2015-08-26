<?php
/*
 * This file is part of Flarum.
 *
 * (c) Toby Zerner <toby.zerner@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Flarum\Forum\Actions;

use Flarum\Support\ClientAction as BaseClientAction;
use Flarum\Api\Client;
use Flarum\Core\Settings\SettingsRepository;
use Flarum\Locale\LocaleManager;

class ClientAction extends BaseClientAction
{
    /**
     * {@inheritdoc}
     */
    protected $clientName = 'forum';

    /**
     * {@inheritdoc}
     */
    protected $translationKeys = [
        'core.account',
        'core.activity',
        'core.administration',
        'core.alert',
        'core.all_discussions',
        'core.before_log_in_link',
        'core.before_sign_up_link',
        'core.bio_placeholder',
        'core.cannot_reply',
        'core.cannot_reply_help',
        'core.change_email',
        'core.change_password',
        'core.change_password_help',
        'core.close',
        'core.confirm_delete_discussion',
        'core.confirm_discard_discussion',
        'core.confirm_discard_edit',
        'core.confirm_discard_reply',
        'core.confirmation_email_sent',
        'core.controls',
        'core.delete',
        'core.delete_forever',
        'core.deleted',
        'core.disclose_online',
        'core.discussion_renamed_post',
        'core.discussion_renamed_notification',
        'core.discussion_replied',
        'core.discussion_started',
        'core.discussion_title',
        'core.discussions',
        'core.dismiss',
        'core.edit',
        'core.editing_post',
        'core.email',
        'core.email_confirmation_required',
        'core.exit_full_screen',
        'core.forgot_password',
        'core.forgot_password_help',
        'core.forgot_password_link',
        'core.full_screen',
        'core.go_to',
        'core.group_admin',
        'core.group_admins',
        'core.group_guest',
        'core.group_guests',
        'core.group_member',
        'core.group_members',
        'core.group_mod',
        'core.group_mods',
        'core.invalid_login',
        'core.joined',
        'core.joined_the_forum',
        'core.load_more',
        'core.log_in',
        'core.log_in_to_reply',
        'core.log_out',
        'core.mark_all_as_read',
        'core.mark_as_read',
        'core.minimize',
        'core.no_notifications',
        'core.notifications',
        'core.notify_discussion_renamed',
        'core.now',
        'core.online',
        'core.original_post',
        'core.password',
        'core.password_reset_email_sent',
        'core.period_later',
        'core.post_discussion',
        'core.post_edited',
        'core.post_number',
        'core.post_reply',
        'core.posted_a_reply',
        'core.posts',
        'core.powered_by_flarum',
        'core.privacy',
        'core.profile',
        'core.prompt_rename_discussion',
        'core.recover_password',
        'core.refresh',
        'core.remove',
        'core.rename',
        'core.reply',
        'core.reply_posted',
        'core.restore',
        'core.save_changes',
        'core.search_all_discussions',
        'core.search_forum',
        'core.send_password_reset_email',
        'core.settings',
        'core.sign_up',
        'core.sort_latest',
        'core.sort_newest',
        'core.sort_oldest',
        'core.sort_relevance',
        'core.sort_top',
        'core.start_a_discussion',
        'core.started_a_discussion',
        'core.unread_posts',
        'core.upload',
        'core.username',
        'core.username_or_email',
        'core.users',
        'core.view',
        'core.viewing_posts',
        'core.welcome_user',
        'core.write_a_post',
        'core.write_a_reply'
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(Client $apiClient, LocaleManager $locales, SettingsRepository $settings)
    {
        parent::__construct($apiClient, $locales, $settings);

        $this->layout = __DIR__.'/../../../views/forum.blade.php';
    }

    /**
     * @inheritdoc
     */
    protected function getAssets()
    {
        $assets = parent::getAssets();

        // Add the formatter JavaScript payload.
        $assets->addJs(function () {
            return app('flarum.formatter')->getJS();
        });

        return $assets;
    }
}
