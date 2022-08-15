import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';

import SettingsPage from 'flarum/forum/components/SettingsPage';
import FieldSet from 'flarum/common/components/FieldSet';
import Switch from 'flarum/common/components/Switch';

export default function addUserPreferences() {
  extend(SettingsPage.prototype, 'settingsItems', function (items) {
    items.add(
      'fof-subscriptions',
      <FieldSet label={app.translator.trans('flarum-subscriptions.forum.user_preferences.heading')} className="Settings-subscriptions">
        <Switch
          id="flarum_subscriptions__notify_for_all_posts"
          state={!!this.user!.preferences()?.['flarum-subscriptions.notify_for_all_posts']}
          onchange={(val: boolean) => {
            this.user!.savePreferences({ 'flarum-subscriptions.notify_for_all_posts': val });
          }}
        >
          {app.translator.trans('flarum-subscriptions.forum.user_preferences.notify_for_all_posts_label')}
        </Switch>
      </FieldSet>
    );
  });
}
