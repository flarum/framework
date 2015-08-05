import { extend } from 'flarum/extend';
import app from 'flarum/app';
import PermissionGrid from 'flarum/components/PermissionGrid';

app.initializers.add('suspend', () => {
  extend(PermissionGrid.prototype, 'moderateItems', items => {
    items.add('suspendUsers', {
      label: 'Suspend users',
      permission: 'user.suspend'
    });
  });
});
