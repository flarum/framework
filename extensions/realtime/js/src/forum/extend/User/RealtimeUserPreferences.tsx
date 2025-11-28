import app from 'flarum/forum/app';
import User from 'flarum/common/models/User';
import ItemList from 'flarum/common/utils/ItemList';
import Switch from 'flarum/common/components/Switch';
import type Mithril from 'mithril';

export default function RealtimeUserPreferencesItems(user?: User): ItemList<Mithril.Children> {
  const items = new ItemList<Mithril.Children>();

  let typingIndicatorLoading = false;

  items.add(
    'typing-indicator',
    [
      <Switch
        state={user!.preferences()?.['flarum-realtime.typing-indicator-full']}
        onchange={(value: boolean) => {
          typingIndicatorLoading = true;

          user!.savePreferences({ 'flarum-realtime.typing-indicator-full': value }).then(() => {
            typingIndicatorLoading = false;
            m.redraw();
          });
        }}
        loading={typingIndicatorLoading}
      >
        {app.translator.trans('flarum-realtime.forum.user.settings.typing-indicator-type.label')}
      </Switch>,
      <p className="helpText">{app.translator.trans('flarum-realtime.forum.user.settings.typing-indicator-type.help')}</p>,
    ],
    80
  );

  return items;
}
