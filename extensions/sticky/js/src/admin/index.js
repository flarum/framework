import { extend } from 'flarum/extend';
import app from 'flarum/app';
import PermissionGrid from 'flarum/components/PermissionGrid';

app.initializers.add('flarum-sticky', () => {
  extend(PermissionGrid.prototype, 'moderateItems', items => {
    items.add('sticky', {
      icon: 'fas fa-thumbtack',
      label: app.translator.trans('flarum-sticky.admin.permissions.sticky_discussions_label'),
      permission: 'discussion.sticky'
    }, 95);
  });
});
