import PostComment from 'flarum/components/post-comment';
import PostDiscussionRenamed from 'flarum/components/post-discussion-renamed';
import ActivityPost from 'flarum/components/activity-post';
import ActivityJoin from 'flarum/components/activity-join';
import NotificationDiscussionRenamed from 'flarum/components/notification-discussion-renamed';

export default function(app) {
  app.postComponentRegistry = {
    comment: PostComment,
    discussionRenamed: PostDiscussionRenamed
  };

  app.activityComponentRegistry = {
    post: ActivityPost,
    join: ActivityJoin
  };

  app.notificationComponentRegistry = {
    discussionRenamed: NotificationDiscussionRenamed
  };
}
