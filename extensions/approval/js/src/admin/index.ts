import { extend } from 'flarum/common/extend';
import app from 'flarum/admin/app';

export { default as extend } from './extend';

app.initializers.add('flarum-approval', () => {
  extend(app, 'getRequiredPermissions', function (required, permission) {
    if (permission === 'discussion.startWithoutApproval') {
      required.push('startDiscussion');
    }
    if (permission === 'discussion.replyWithoutApproval') {
      required.push('discussion.reply');
    }
  });
});
