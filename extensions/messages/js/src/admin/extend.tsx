import Extend from 'flarum/common/extenders';
import commonExtend from '../common/extend';
import app from 'flarum/admin/app';
import SettingDropdown from 'flarum/admin/components/SettingDropdown';

export default [
  ...commonExtend,

  new Extend.Admin()
    .section(
      () => ({
        id: 'groups',
        icon: 'fas fa-users',
        label: app.translator.trans('flarum-messages.admin.sections.groups.label'),
        description: app.translator.trans('flarum-messages.admin.sections.groups.description'),
      }),
      10
    )
    // Its default is registered in extend.php, where the server clamps it to 1–50 too.
    .setting(() => ({
      setting: 'flarum-messages.max_recipients',
      section: 'groups',
      type: 'number',
      min: 1,
      max: 50,
      label: app.translator.trans('flarum-messages.admin.settings.max_recipients_label'),
      help: app.translator.trans('flarum-messages.admin.settings.max_recipients_help'),
    }))
    // Off by default; registered in extend.php.
    .setting(() => ({
      setting: 'flarum-messages.allow_group_names',
      section: 'groups',
      type: 'boolean',
      label: app.translator.trans('flarum-messages.admin.settings.allow_group_names_label'),
      help: app.translator.trans('flarum-messages.admin.settings.allow_group_names_help'),
    }))
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
    )
    .permission(
      () => ({
        icon: 'fas fa-envelope-circle-check',
        label: app.translator.trans('flarum-messages.admin.permissions.message_users_without_permission_label'),
        permission: 'dialog.messageUsersWithoutPermission',
        allowGuest: false,
      }),
      'moderate',
      96
    )
    .permission(
      () => ({
        icon: 'fas fa-bolt',
        label: app.translator.trans('flarum-messages.admin.permissions.send_without_throttle_label'),
        permission: 'dialog.sendMessageWithoutThrottle',
        allowGuest: false,
      }),
      'moderate',
      95
    )
    .permission(
      () => ({
        icon: 'fas fa-bullseye',
        label: app.translator.trans('flarum-messages.admin.permissions.view_ips_label'),
        permission: 'dialog.viewIps',
        allowGuest: false,
      }),
      'moderate',
      94
    ),
];
