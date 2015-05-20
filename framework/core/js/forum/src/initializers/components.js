import CommentPost from 'flarum/components/comment-post';
import DiscussionRenamedPost from 'flarum/components/discussion-renamed-post';
import PostedActivity from 'flarum/components/posted-activity';
import JoinedActivity from 'flarum/components/joined-activity';
import DiscussionRenamedNotification from 'flarum/components/discussion-renamed-notification';

export default function(app) {
  app.postComponentRegistry = {
    'comment': CommentPost,
    'discussionRenamed': DiscussionRenamedPost
  };

  app.activityComponentRegistry = {
    'posted': PostedActivity,
    'startedDiscussion': PostedActivity,
    'joined': JoinedActivity
  };

  app.notificationComponentRegistry = {
    'discussionRenamed': DiscussionRenamedNotification
  };
}
