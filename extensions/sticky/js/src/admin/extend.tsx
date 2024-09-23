import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';
import commonExtend from '../common/extend';

export default [
  ...commonExtend,

  new Extend.Admin().permission(
    () => ({
      icon: 'fas fa-thumbtack',
      label: app.translator.trans('flarum-sticky.admin.permissions.sticky_discussions_label'),
      permission: 'discussion.sticky',
    }),
    'moderate',
    95
  ),
];
