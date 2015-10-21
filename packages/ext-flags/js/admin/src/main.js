import { extend } from 'flarum/extend';
import app from 'flarum/app';
import PermissionGrid from 'flarum/components/PermissionGrid';

app.initializers.add('flarum-flags', () => {
  extend(PermissionGrid.prototype, 'moderateItems', items => {
    items.add('viewFlags', {
      icon: 'flag',
      label: app.translator.trans('flarum-flags.admin.permissions.view_flags'),
      permission: 'discussion.viewFlags'
    }, 65);
  });

  extend(PermissionGrid.prototype, 'replyItems', items => {
    items.add('flagPosts', {
      icon: 'flag',
      label: app.translator.trans('flarum-flags.admin.permissions.flag_posts'),
      permission: 'discussion.flagPosts'
    }, 70);
  });
});
