import app from 'flarum/app';
import SettingsPage from 'flarum/components/settings-page';
import { extend } from 'flarum/extension-utils';
import icon from 'flarum/helpers/icon';

import postMentionPreviews from 'mentions/post-mention-previews';
import mentionedByList from 'mentions/mentioned-by-list';
import postReplyAction from 'mentions/post-reply-action';
import composerAutocomplete from 'mentions/composer-autocomplete';
import PostMentionedNotification from 'mentions/components/post-mentioned-notification';
import UserMentionedNotification from 'mentions/components/user-mentioned-notification';

app.initializers.add('mentions', function() {
  // For every mention of a post inside a post's content, set up a hover handler
  // that shows a preview of the mentioned post.
  postMentionPreviews();

  // In the footer of each post, show information about who has replied (i.e.
  // who the post has been mentioned by).
  mentionedByList();

  // Add a 'reply' control to the footer of each post. When clicked, it will
  // open up the composer and add a post mention to its contents.
  postReplyAction();

  // After typing '@' in the composer, show a dropdown suggesting a bunch of
  // posts or users that the user could mention.
  composerAutocomplete();

  app.notificationComponentRegistry['postMentioned'] = PostMentionedNotification;
  app.notificationComponentRegistry['userMentioned'] = UserMentionedNotification;

  // Add notification preferences.
  extend(SettingsPage.prototype, 'notificationTypes', function(items) {
    items.add('postMentioned', {
      name: 'postMentioned',
      label: [icon('reply'), ' Someone replies to my post']
    });
    items.add('userMentioned', {
      name: 'userMentioned',
      label: [icon('at'), ' Someone mentions me in a post']
    });
  });
});
