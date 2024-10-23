import Extend from 'flarum/common/extenders';
import app from 'flarum/admin/app';

export default [
  new Extend.Admin()
    .permission(
      () => ({
        icon: 'fas fa-check',
        label: app.translator.trans('flarum-approval.admin.permissions.start_discussions_without_approval_label'),
        permission: 'discussion.startWithoutApproval',
      }),
      'start',
      95
    )
    .permission(
      () => ({
        icon: 'fas fa-check',
        label: app.translator.trans('flarum-approval.admin.permissions.reply_without_approval_label'),
        permission: 'discussion.replyWithoutApproval',
      }),
      'reply',
      95
    )
    .permission(
      () => ({
        icon: 'fas fa-check',
        label: app.translator.trans('flarum-approval.admin.permissions.approve_posts_label'),
        permission: 'discussion.approvePosts',
      }),
      'moderate',
      65
    ),
];
