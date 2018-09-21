import { extend } from 'flarum/extend';
import app from 'flarum/app';
import NotificationGrid from 'flarum/components/NotificationGrid';
import { getPlainContent } from 'flarum/utils/string';

import addPostMentionPreviews from './addPostMentionPreviews';
import addMentionedByList from './addMentionedByList';
import addPostReplyAction from './addPostReplyAction';
import addPostQuoteButton from './addPostQuoteButton';
import addComposerAutocomplete from './addComposerAutocomplete';
import PostMentionedNotification from './components/PostMentionedNotification';
import UserMentionedNotification from './components/UserMentionedNotification';
import UserPage from 'flarum/components/UserPage'
import LinkButton from 'flarum/components/LinkButton';
import MentionsUserPage from './components/MentionsUserPage';

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

  // Show a Quote button when Post text is selected
  addPostQuoteButton();

  // After typing '@' in the composer, show a dropdown suggesting a bunch of
  // posts or users that the user could mention.
  addComposerAutocomplete();

  app.notificationComponents.postMentioned = PostMentionedNotification;
  app.notificationComponents.userMentioned = UserMentionedNotification;

  // Add notification preferences.
  extend(NotificationGrid.prototype, 'notificationTypes', function(items) {
    items.add('postMentioned', {
      name: 'postMentioned',
      icon: 'fas fa-reply',
      label: app.translator.trans('flarum-mentions.forum.settings.notify_post_mentioned_label')
    });

    items.add('userMentioned', {
      name: 'userMentioned',
      icon: 'fas fa-at',
      label: app.translator.trans('flarum-mentions.forum.settings.notify_user_mentioned_label')
    });
  });

  // Add mentions tab in user profile
  app.routes['user.mentions'] = {path: '/u/:username/mentions', component: MentionsUserPage.component()};
  extend(UserPage.prototype, 'navItems', function(items) {
    const user = this.user;
    items.add('mentions',
      LinkButton.component({
        href: app.route('user.mentions', {username: user.username()}),
        name: 'mentions',
        children: [app.translator.trans('flarum-mentions.forum.user.mentions_link')],
        icon: 'fas fa-at'
      }),
      80
    );
  });

  // Remove post mentions when rendering post previews.
  getPlainContent.removeSelectors.push('a.PostMention');
});

export * from './utils/textFormatter';
