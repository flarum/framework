System.register('flarum/approval/main', ['flarum/extend', 'flarum/app', 'flarum/models/Discussion', 'flarum/models/Post', 'flarum/components/DiscussionListItem', 'flarum/components/CommentPost', 'flarum/components/Button', 'flarum/utils/PostControls'], function (_export) {
  'use strict';

  var extend, override, app, Discussion, Post, DiscussionListItem, CommentPost, Button, PostControls;
  return {
    setters: [function (_flarumExtend) {
      extend = _flarumExtend.extend;
      override = _flarumExtend.override;
    }, function (_flarumApp) {
      app = _flarumApp['default'];
    }, function (_flarumModelsDiscussion) {
      Discussion = _flarumModelsDiscussion['default'];
    }, function (_flarumModelsPost) {
      Post = _flarumModelsPost['default'];
    }, function (_flarumComponentsDiscussionListItem) {
      DiscussionListItem = _flarumComponentsDiscussionListItem['default'];
    }, function (_flarumComponentsCommentPost) {
      CommentPost = _flarumComponentsCommentPost['default'];
    }, function (_flarumComponentsButton) {
      Button = _flarumComponentsButton['default'];
    }, function (_flarumUtilsPostControls) {
      PostControls = _flarumUtilsPostControls['default'];
    }],
    execute: function () {

      app.initializers.add('flarum-approval', function () {
        Discussion.prototype.isApproved = Discussion.attribute('isApproved');

        Post.prototype.isApproved = Post.attribute('isApproved');
        Post.prototype.canApprove = Post.attribute('canApprove');

        extend(DiscussionListItem.prototype, 'attrs', function (attrs) {
          if (!this.props.discussion.isApproved()) {
            attrs.className += ' DiscussionListItem--unapproved';
          }
        });

        extend(CommentPost.prototype, 'attrs', function (attrs) {
          if (!this.props.post.isApproved() && !this.props.post.isHidden()) {
            attrs.className += ' CommentPost--unapproved';
          }
        });

        extend(CommentPost.prototype, 'headerItems', function (items) {
          if (!this.props.post.isApproved() && !this.props.post.isHidden()) {
            items.add('unapproved', 'Awaiting Approval');
          }
        });

        override(CommentPost.prototype, 'flagReason', function (original, flag) {
          if (flag.type() === 'approval') {
            return 'Awaiting approval';
          }

          return original(flag);
        });

        extend(PostControls, 'destructiveControls', function (items, post) {
          if (!post.isApproved() && post.canApprove()) {
            items.add('approve', m(
              Button,
              { icon: 'check', onclick: PostControls.approveAction.bind(post) },
              'Approve'
            ), 10);
          }
        });

        PostControls.approveAction = function () {
          this.save({ isApproved: true });
        };
      }, -10); // set initializer priority to run after reports
    }
  };
});