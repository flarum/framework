import { extend } from 'flarum/extend';
import app from 'flarum/app';
import PermissionGrid from 'flarum/components/PermissionGrid';

app.initializers.add('sticky', () => {
  extend(PermissionGrid.prototype, 'moderateItems', items => {
    items.add('sticky', {
      icon: 'thumb-tack',
      label: 'Sticky discussions',
      permission: 'discussion.sticky'
    }, 95);
  });
});
