import { extend } from 'flarum/extend';
import app from 'flarum/app';
import PermissionGrid from 'flarum/components/PermissionGrid';

app.initializers.add('flarum-approval', () => {
  extend(app, 'getRequiredPermissions', function(required, permission) {
    if (permission === 'discussion.startWithoutApproval') {
      required.push('startDiscussion');
    }
    if (permission === 'discussion.replyWithoutApproval') {
      required.push('discussion.reply');
    }
  });

  extend(PermissionGrid.prototype, 'startItems', items => {
    items.add('startDiscussionsWithoutApproval', {
      icon: 'fas fa-check',
      label: app.translator.trans('flarum-approval.admin.permissions.start_discussions_without_approval_label'),
      permission: 'discussion.startWithoutApproval'
    }, 95);
  });

  extend(PermissionGrid.prototype, 'replyItems', items => {
    items.add('replyWithoutApproval', {
      icon: 'fas fa-check',
      label: app.translator.trans('flarum-approval.admin.permissions.reply_without_approval_label'),
      permission: 'discussion.replyWithoutApproval'
    }, 95);
  });

  extend(PermissionGrid.prototype, 'moderateItems', items => {
    items.add('approvePosts', {
      icon: 'fas fa-check',
      label: app.translator.trans('flarum-approval.admin.permissions.approve_posts_label'),
      permission: 'discussion.approvePosts'
    }, 65);
  });
});
