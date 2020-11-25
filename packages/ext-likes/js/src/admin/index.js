import { extend } from 'flarum/extend';
import app from 'flarum/app';
import PermissionGrid from 'flarum/components/PermissionGrid';

app.initializers.add('flarum-likes', () => {
  app.extensionData
    .for('flarum-likes')
    .registerPermission({
      icon: 'far fa-thumbs-up',
      label: app.translator.trans('flarum-likes.admin.permissions.like_posts_label'),
      permission: 'discussion.likePosts'
    }, 'reply');
});
