import Extend from 'flarum/common/extenders';
import commonExtend from '../common/extend';
import app from 'flarum/admin/app';
import SettingDropdown from 'flarum/admin/components/SettingDropdown';

export default [
  ...commonExtend,

  new Extend.Admin()
    .permission(
      () => ({
        icon: 'fas fa-envelope-open-text',
        label: app.translator.trans('flarum-messages.admin.permissions.send_messages_label'),
        permission: 'dialog.sendMessage',
        allowGuest: false,
      }),
      'start',
      95
    )
    .permission(
      () => ({
        icon: 'far fa-trash-alt',
        label: app.translator.trans('flarum-messages.admin.permissions.delete_own_messages_label'),
        id: 'flarum-messages.allow_delete_own_messages',
        setting: () => {
          const minutes = parseInt(app.data.settings['flarum-messages.allow_delete_own_messages'], 10);

          return (
            <SettingDropdown
              default={'0'}
              key="flarum-messages.allow_delete_own_messages"
              options={[
                { value: '-1', label: app.translator.trans('core.admin.permissions_controls.allow_indefinitely_button') },
                { value: '10', label: app.translator.trans('core.admin.permissions_controls.allow_ten_minutes_button') },
                { value: 'reply', label: app.translator.trans('core.admin.permissions_controls.allow_until_reply_button') },
                { value: '0', label: app.translator.trans('core.admin.permissions_controls.allow_never_button') },
              ]}
            />
          );
        },
      }),
      'reply',
      80
    ),
];
