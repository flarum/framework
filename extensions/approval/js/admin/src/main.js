import { extend } from 'flarum/extend';
import app from 'flarum/app';
import PermissionGrid from 'flarum/components/PermissionGrid';

app.initializers.add('approval', () => {
  extend(PermissionGrid.prototype, 'replyItems', items => {
    items.add('replyWithoutApproval', {
      icon: 'check',
      label: 'Reply without approval',
      permission: 'discussion.replyWithoutApproval'
    }, 95);
  });

  extend(PermissionGrid.prototype, 'moderateItems', items => {
    items.add('approvePosts', {
      icon: 'check',
      label: 'Approve posts',
      permission: 'discussion.approvePosts'
    }, 65);
  });
});
