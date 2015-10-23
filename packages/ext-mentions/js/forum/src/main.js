import { extend } from 'flarum/extend';
import app from 'flarum/app';
import NotificationGrid from 'flarum/components/NotificationGrid';
import { getPlainContent } from 'flarum/utils/string';

import addPostMentionPreviews from 'flarum/mentions/addPostMentionPreviews';
import addMentionedByList from 'flarum/mentions/addMentionedByList';
import addPostReplyAction from 'flarum/mentions/addPostReplyAction';
import addComposerAutocomplete from 'flarum/mentions/addComposerAutocomplete';
import PostMentionedNotification from 'flarum/mentions/components/PostMentionedNotification';
import UserMentionedNotification from 'flarum/mentions/components/UserMentionedNotification';

app.initializers.add('flarum-mentions', function() {
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
      label: app.translator.trans('flarum-mentions.forum.settings.notify_post_mentioned_label')
    });

    items.add('userMentioned', {
      name: 'userMentioned',
      icon: 'at',
      label: app.translator.trans('flarum-mentions.forum.settings.notify_user_mentioned_label')
    });
  });

  getPlainContent.removeSelectors.push('a.PostMention');
});
