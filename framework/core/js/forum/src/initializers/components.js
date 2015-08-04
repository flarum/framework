import CommentPost from 'flarum/components/CommentPost';
import DiscussionRenamedPost from 'flarum/components/DiscussionRenamedPost';
import PostedActivity from 'flarum/components/PostedActivity';
import JoinedActivity from 'flarum/components/JoinedActivity';
import DiscussionRenamedNotification from 'flarum/components/DiscussionRenamedNotification';

/**
 * The `components` initializer registers components to display the default post
 * types, activity types, and notifications type with the application.
 *
 * @param {ForumApp} app
 */
export default function components(app) {
  app.postComponents.comment = CommentPost;
  app.postComponents.discussionRenamed = DiscussionRenamedPost;

  app.notificationComponents.discussionRenamed = DiscussionRenamedNotification;
}
