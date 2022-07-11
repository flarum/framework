import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';

import SettingsPage from 'flarum/forum/components/SettingsPage';
import FieldSet from 'flarum/common/components/FieldSet';
import Select from 'flarum/common/components/Select';

import type Mithril from 'mithril';

const keys = ['all_new', 'first_new'];

export default function addUserPreferences() {
  extend(SettingsPage.prototype, 'settingsItems', function (items) {
    // Option disabled by admin
    if (app.forum.attribute('flarum-subscriptions.enforce_notification_criteria')) return;

    const selectOptions = keys.reduce((acc, key) => {
      acc[key] = app.translator.trans(`flarum-subscriptions.forum.user_preferences.subscription_notification_criteria.options.${key}`);
      return acc;
    }, {} as Record<string, Mithril.Children>);

    items.add(
      'fof-subscriptions',
      <FieldSet label={app.translator.trans('flarum-subscriptions.forum.user_preferences.heading')} className="Settings-subscriptions">
        <div className="Form-group">
          <label for="flarum_subscriptions__user_notification_criteria">
            {app.translator.trans('flarum-subscriptions.forum.user_preferences.subscription_notification_criteria.label')}
          </label>

          <p id="flarum_subscriptions__user_notification_criteria__help" class="helpText">
            {app.translator.trans('flarum-subscriptions.forum.user_preferences.subscription_notification_criteria.help')}
          </p>

          <Select
            id="flarum_subscriptions__user_notification_criteria"
            aria-describedby="flarum_subscriptions__user_notification_criteria__help"
            value={
              this.user.preferences()['flarum-subscriptions.user_notification_criteria'] ||
              app.forum.attribute('flarum-subscriptions.default_notification_criteria')
            }
            options={selectOptions}
            onchange={(val: string) => {
              this.user.savePreferences({ 'flarum-subscriptions.user_notification_criteria': val });
            }}
          />
        </div>
      </FieldSet>
    );
  });
}
