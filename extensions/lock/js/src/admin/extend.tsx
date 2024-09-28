import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import commonExtend from '../common/extend';

export default [
  ...commonExtend,

  new Extend.Admin().permission(
    () => ({
      icon: 'fas fa-lock',
      label: app.translator.trans('flarum-lock.admin.permissions.lock_discussions_label'),
      permission: 'discussion.lock',
    }),
    'moderate',
    95
  ),
];
