import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import SettingsPage from 'flarum/forum/components/SettingsPage';
import Switch from 'flarum/common/components/Switch';

export default function () {
  extend(SettingsPage.prototype, 'notificationsItems', function (this: SettingsPage, items) {
    items.add(
      'followAfterReply',
      <Switch
        state={this.user.preferences().followAfterReply}
        onchange={(value) => {
          this.followAfterReplyLoading = true;

          this.user.savePreferences({ followAfterReply: value }).then(() => {
            this.followAfterReplyLoading = false;
            m.redraw();
          });
        }}
        loading={this.followAfterReplyLoading}
      >
        {app.translator.trans('flarum-subscriptions.forum.settings.follow_after_reply_label')}
      </Switch>
    );

    items.add(
      'notifyForAllPosts',
      <Switch
        id="flarum_subscriptions__notify_for_all_posts"
        state={!!this.user!.preferences()?.['flarum-subscriptions.notify_for_all_posts']}
        onchange={(val: boolean) => {
          this.user!.savePreferences({ 'flarum-subscriptions.notify_for_all_posts': val });
        }}
      >
        {app.translator.trans('flarum-subscriptions.forum.settings.notify_for_all_posts_label')}
      </Switch>
    );
  });
}
