import { extend } from 'flarum/extend';
import app from 'flarum/app';
import PermissionGrid from 'flarum/components/PermissionGrid';

app.initializers.add('lock', () => {
  extend(PermissionGrid.prototype, 'moderateItems', items => {
    items.add('lock', {
      icon: 'lock',
      label: 'Lock discussions',
      permission: 'discussion.lock'
    }, 95);
  });
});
