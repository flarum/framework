import { extend, override } from 'flarum/extend';
import app from 'flarum/app';
import Discussion from 'flarum/models/Discussion';
import Post from 'flarum/models/Post';
import DiscussionListItem from 'flarum/components/DiscussionListItem';
import CommentPost from 'flarum/components/CommentPost';
import Button from 'flarum/components/Button';
import PostControls from 'flarum/utils/PostControls';

app.initializers.add('flarum-approval', () => {
  Discussion.prototype.isApproved = Discussion.attribute('isApproved');

  Post.prototype.isApproved = Post.attribute('isApproved');
  Post.prototype.canApprove = Post.attribute('canApprove');

  extend(DiscussionListItem.prototype, 'attrs', function(attrs) {
    if (!this.props.discussion.isApproved()) {
      attrs.className += ' DiscussionListItem--unapproved';
    }
  });

  extend(CommentPost.prototype, 'attrs', function(attrs) {
    if (!this.props.post.isApproved() && !this.props.post.isHidden()) {
      attrs.className += ' CommentPost--unapproved';
    }
  });

  extend(CommentPost.prototype, 'headerItems', function(items) {
    if (!this.props.post.isApproved() && !this.props.post.isHidden()) {
      items.add('unapproved', 'Awaiting Approval');
    }
  });

  override(CommentPost.prototype, 'flagReason', function(original, flag) {
    if (flag.type() === 'approval') {
      return 'Awaiting approval';
    }

    return original(flag);
  });

  extend(PostControls, 'destructiveControls', function(items, post) {
    if (!post.isApproved() && post.canApprove()) {
      items.add('approve',
        <Button icon="check" onclick={PostControls.approveAction.bind(post)}>
          Approve
        </Button>,
        10
      );
    }
  });

  PostControls.approveAction = function() {
    this.save({isApproved: true});
  };
}, -10); // set initializer priority to run after reports
