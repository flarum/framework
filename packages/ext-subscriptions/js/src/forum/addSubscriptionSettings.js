import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import SettingsPage from 'flarum/forum/components/SettingsPage';
import Switch from 'flarum/common/components/Switch';

export default function () {
  extend(SettingsPage.prototype, 'notificationsItems', function (items) {
    items.add(
      'followAfterReply',
      Switch.component(
        {
          state: this.user.preferences().followAfterReply,
          onchange: (value) => {
            this.followAfterReplyLoading = true;

            this.user.savePreferences({ followAfterReply: value }).then(() => {
              this.followAfterReplyLoading = false;
              m.redraw();
            });
          },
          loading: this.followAfterReplyLoading,
        },
        app.translator.trans('flarum-subscriptions.forum.settings.follow_after_reply_label')
      )
    );
  });
}
