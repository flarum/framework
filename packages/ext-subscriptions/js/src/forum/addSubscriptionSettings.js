import { extend } from 'flarum/extend';
import SettingsPage from 'flarum/components/SettingsPage';
import Switch from 'flarum/components/Switch';

export default function() {
  extend(SettingsPage.prototype, 'notificationsItems', function(items) {
    items.add('followAfterReply',
      Switch.component({
        children: app.translator.trans('flarum-subscriptions.forum.settings.follow_after_reply_label'),
        state: this.user.preferences().followAfterReply,
        onchange: this.preferenceSaver('followAfterReply')
      })
    );
  });
}
