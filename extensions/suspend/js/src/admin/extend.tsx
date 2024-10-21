import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import commonExtend from '../common/extend';

export default [
  ...commonExtend,

  new Extend.Admin().permission(
    () => ({
      icon: 'fas fa-ban',
      label: app.translator.trans('flarum-suspend.admin.permissions.suspend_users_label'),
      permission: 'user.suspend',
    }),
    'moderate'
  ),
];
