import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';

import addLikeAction from './addLikeAction';
import addLikesList from './addLikesList';
import PostLikedNotification from './components/PostLikedNotification';
import addLikesTabToUserProfile from './addLikesTabToUserProfile';

export { default as extend } from './extend';

app.initializers.add('flarum-likes', () => {
  app.notificationComponents.postLiked = PostLikedNotification;

  addLikeAction();
  addLikesList();
  addLikesTabToUserProfile();

  extend('flarum/forum/components/NotificationGrid', 'notificationTypes', function (items) {
    items.add('postLiked', {
      name: 'postLiked',
      icon: 'far fa-thumbs-up',
      label: app.translator.trans('flarum-likes.forum.settings.notify_post_liked_label'),
    });
  });
});
