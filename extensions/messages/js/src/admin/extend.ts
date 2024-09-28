import Extend from 'flarum/common/extenders';
import commonExtend from '../common/extend';
import app from 'flarum/admin/app';

export default [
  ...commonExtend,

  new Extend.Admin().permission(
    () => ({
      icon: 'fas fa-envelope-open-text',
      label: app.translator.trans('flarum-messages.admin.permissions.send_messages'),
      permission: 'dialog.sendMessage',
      allowGuest: false,
    }),
    'start',
    98
  ),
];
