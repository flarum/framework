import { extend, override } from 'flarum/common/extend';
import app from 'flarum/forum/app';

import addLikeAction from './addLikeAction';
import addLikesList from './addLikesList';
import addLikesTabToUserProfile from './addLikesTabToUserProfile';

export { default as extend } from './extend';

app.initializers.add('flarum-likes', () => {
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

  // Auto scope the search to the current user liked posts.
  override('flarum/forum/components/SearchModal', 'defaultActiveSource', function (original) {
    const orig = original();

    if (!orig && app.current.data.routeName && app.current.data.routeName.includes('user.likes') && app.current.data.user) {
      return 'posts';
    }

    return orig;
  });
  extend('flarum/forum/components/SearchModal', 'defaultFilters', function (filters) {
    if (app.current.data.routeName && app.current.data.routeName.includes('user.likes') && app.current.data.user) {
      filters.posts.likedBy = app.current.data.user.id();
    }
  });
});
