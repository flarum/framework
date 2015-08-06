import { extend } from 'flarum/extend';
import app from 'flarum/app';
import NotificationGrid from 'flarum/components/NotificationGrid';
import { getPlainContent } from 'flarum/utils/string';

import addPostMentionPreviews from 'mentions/addPostMentionPreviews';
import addMentionedByList from 'mentions/addMentionedByList';
import addPostReplyAction from 'mentions/addPostReplyAction';
import addComposerAutocomplete from 'mentions/addComposerAutocomplete';
import PostMentionedNotification from 'mentions/components/PostMentionedNotification';
import UserMentionedNotification from 'mentions/components/UserMentionedNotification';

app.initializers.add('mentions', function() {
  // For every mention of a post inside a post's content, set up a hover handler
  // that shows a preview of the mentioned post.
  addPostMentionPreviews();

  // In the footer of each post, show information about who has replied (i.e.
  // who the post has been mentioned by).
  addMentionedByList();

  // Add a 'reply' control to the footer of each post. When clicked, it will
  // open up the composer and add a post mention to its contents.
  addPostReplyAction();

  // After typing '@' in the composer, show a dropdown suggesting a bunch of
  // posts or users that the user could mention.
  addComposerAutocomplete();

  app.notificationComponents.postMentioned = PostMentionedNotification;
  app.notificationComponents.userMentioned = UserMentionedNotification;

  // Add notification preferences.
  extend(NotificationGrid.prototype, 'notificationTypes', function(items) {
    items.add('postMentioned', {
      name: 'postMentioned',
      icon: 'reply',
      label: app.trans('mentions.notify_post_mentioned')
    });

    items.add('userMentioned', {
      name: 'userMentioned',
      icon: 'at',
      label: app.trans('mentions.notify_user_mentioned')
    });
  });

  getPlainContent.removeSelectors.push('a.PostMention');
});
