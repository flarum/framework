import CommentPost from 'flarum/components/comment-post';
import DiscussionRenamedPost from 'flarum/components/discussion-renamed-post';
import PostActivity from 'flarum/components/post-activity';
import JoinActivity from 'flarum/components/join-activity';
import DiscussionRenamedNotification from 'flarum/components/discussion-renamed-notification';

export default function(app) {
  app.postComponentRegistry = {
    comment: CommentPost,
    discussionRenamed: DiscussionRenamedPost
  };

  app.activityComponentRegistry = {
    post: PostActivity,
    join: JoinActivity
  };

  app.notificationComponentRegistry = {
    discussionRenamed: DiscussionRenamedNotification
  };
}
