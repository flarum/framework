import { extend, override } from 'flarum/extension-utils';
import app from 'flarum/app';
import Post from 'flarum/models/post';
import Model from 'flarum/model';
import DiscussionPage from 'flarum/components/discussion-page';
import ActionButton from 'flarum/components/action-button';
import CommentPost from 'flarum/components/comment-post';
import punctuate from 'flarum/helpers/punctuate';
import username from 'flarum/helpers/username';

import PostLikedNotification from 'flarum-likes/components/post-liked-notification';
import PostLikesModal from 'flarum-likes/components/post-likes-modal';

app.initializers.add('flarum-likes', function() {

  app.notificationComponentRegistry['postLiked'] = PostLikedNotification;

  Post.prototype.canLike = Model.prop('canLike');
  Post.prototype.likes = Model.many('likes');

  extend(DiscussionPage.prototype, 'params', function(params) {
    params.include.push('posts.likes');
  });

  extend(CommentPost.prototype, 'footerItems', function(items) {
    var post = this.props.post;
    var likes = post.likes();

    if (likes && likes.length) {

      var limit = 3;

      var names = likes.slice(0, limit).map(user => {
        return m('a', {
          href: app.route.user(user),
          config: m.route
        }, [
          app.session.user() && user === app.session.user() ? 'You' : username(user)
        ])
      });

      if (likes.length > limit + 1) {
        names.push(
          m('a', {
            href: '#',
            onclick: function(e) {
              e.preventDefault();
              app.modal.show(new PostLikesModal({ post }));
            }
          }, (likes.length - limit)+' others')
        );
      }

      items.add('liked',
        m('div.liked-by', [
          punctuate(names),
          ' like this.'
        ])
      );
    }
  });

  extend(CommentPost.prototype, 'actionItems', function(items) {
    var post = this.props.post;
    if (post.isHidden() || !post.canLike()) return;

    var isLiked = app.session.user() && post.likes().some(user => user === app.session.user());

    items.add('like',
      ActionButton.component({
        icon: 'thumbs-o-up',
        label: isLiked ? 'Unlike' : 'Like',
        onclick: () => {
          isLiked = !isLiked;

          post.save({ isLiked });

          var linkage = post.data().links.likes.linkage;
          linkage.some((like, i) => {
            if (like.id == app.session.user().id()) {
              linkage.splice(i, 1);
              return true;
            }
          });

          if (isLiked) {
            linkage.unshift({ type: 'users', id: app.session.user().id() });
          }

          m.redraw();
        }
      })
    );
  });

});
