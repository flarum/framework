import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Post from 'flarum/common/models/Post';
import Model from 'flarum/common/Model';
import NotificationGrid from 'flarum/forum/components/NotificationGrid';

import addLikeAction from './addLikeAction';
import addLikesList from './addLikesList';
import PostLikedNotification from './components/PostLikedNotification';

app.initializers.add('flarum-likes', () => {
  app.notificationComponents.postLiked = PostLikedNotification;

  Post.prototype.canLike = Model.attribute('canLike');
  Post.prototype.likes = Model.hasMany('likes');

  addLikeAction();
  addLikesList();

  extend(NotificationGrid.prototype, 'notificationTypes', function (items) {
    items.add('postLiked', {
      name: 'postLiked',
      icon: 'far fa-thumbs-up',
      label: app.translator.trans('flarum-likes.forum.settings.notify_post_liked_label'),
    });
  });
});
