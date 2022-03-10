import { extend } from 'flarum/common/extend';
import app from 'flarum/admin/app';

app.initializers.add('flarum-approval', () => {
  extend(app, 'getRequiredPermissions', function (required, permission) {
    if (permission === 'discussion.startWithoutApproval') {
      required.push('startDiscussion');
    }
    if (permission === 'discussion.replyWithoutApproval') {
      required.push('discussion.reply');
    }
  });

  app.extensionData
    .for('flarum-approval')
    .registerPermission(
      {
        icon: 'fas fa-check',
        label: app.translator.trans('flarum-approval.admin.permissions.start_discussions_without_approval_label'),
        permission: 'discussion.startWithoutApproval',
      },
      'start',
      95
    )
    .registerPermission(
      {
        icon: 'fas fa-check',
        label: app.translator.trans('flarum-approval.admin.permissions.reply_without_approval_label'),
        permission: 'discussion.replyWithoutApproval',
      },
      'reply',
      95
    )
    .registerPermission(
      {
        icon: 'fas fa-check',
        label: app.translator.trans('flarum-approval.admin.permissions.approve_posts_label'),
        permission: 'discussion.approvePosts',
      },
      'moderate',
      65
    );
});
