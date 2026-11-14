import app from 'flarum/admin/app';
import { extend } from 'flarum/common/extend';
import PermissionGrid from 'flarum/admin/components/PermissionGrid';
import SettingDropdown from 'flarum/admin/components/SettingDropdown';

export default function () {
  extend(PermissionGrid.prototype, 'startItems', (items) => {
    items.add(
      'allowTagChange',
      {
        icon: 'fas fa-tag',
        label: app.translator.trans('flarum-tags.admin.permissions.allow_edit_tags_label'),
        setting: () => {
          const minutes = parseInt(app.data.settings.allow_tag_change, 10);

          // Until an option is saved, authors can't change their tags, which
          // is what Never does: the policy only allows the values below, and
          // a positive number of minutes.
          return (
            <SettingDropdown
              defaultLabel={
                minutes > 0
                  ? app.translator.trans('core.admin.permissions_controls.allow_some_minutes_button', { count: minutes })
                  : app.translator.trans('core.admin.permissions_controls.allow_never_button')
              }
              key="allow_tag_change"
              default="0"
              options={[
                { value: '-1', label: app.translator.trans('core.admin.permissions_controls.allow_indefinitely_button') },
                { value: '10', label: app.translator.trans('core.admin.permissions_controls.allow_ten_minutes_button') },
                { value: 'reply', label: app.translator.trans('core.admin.permissions_controls.allow_until_reply_button') },
                { value: '0', label: app.translator.trans('core.admin.permissions_controls.allow_never_button') },
              ]}
            />
          );
        },
      },
      90
    );
  });
}
