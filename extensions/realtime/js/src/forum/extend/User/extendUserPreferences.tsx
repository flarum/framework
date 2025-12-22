import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import ItemList from 'flarum/common/utils/ItemList';
import FieldSet from 'flarum/common/components/FieldSet';
import type Mithril from 'mithril';
import RealtimeUserPreferencesItems from './RealtimeUserPreferences';

export default function extendUserPreferences() {
  if (!!app.data['flarum-realtime.typing-indicator']) {
    extend('flarum/forum/components/SettingsPage', 'settingsItems', function (items: ItemList<Mithril.Children>) {
      const user = this.user;

      if (!user || !user.canViewWhoTypes()) {
        return;
      }

      items.add(
        'realtimeItems',
        <FieldSet className="Settings-realtime" label={app.translator.trans('flarum-realtime.forum.user.settings.heading')}>
          {RealtimeUserPreferencesItems(user).toArray()}
        </FieldSet>,
        55
      );
    });
  }
}
