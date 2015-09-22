import { extend } from 'flarum/extend';
import app from 'flarum/app';
import PermissionGrid from 'flarum/components/PermissionGrid';

app.initializers.add('likes', () => {
  extend(PermissionGrid.prototype, 'replyItems', items => {
    items.add('likePosts', {
      icon: 'thumbs-o-up',
      label: 'Like posts',
      permission: 'discussion.likePosts'
    });
  });
});
