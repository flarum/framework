import { extend } from 'flarum/extend';
import app from 'flarum/app';
import Button from 'flarum/components/Button';
import CommentPost from 'flarum/components/CommentPost';

export default function() {
  extend(CommentPost.prototype, 'actionItems', function(items) {
    const post = this.attrs.post;

    if (post.isHidden() || !post.canLike()) return;

    const likes = post.likes();

    let isLiked = app.session.user && likes && likes.some(user => user === app.session.user);

    items.add('like',
      Button.component({
        className: 'Button Button--link',
        onclick: () => {
          isLiked = !isLiked;

          post.save({isLiked});

          // We've saved the fact that we do or don't like the post, but in order
          // to provide instantaneous feedback to the user, we'll need to add or
          // remove the like from the relationship data manually.
          const data = post.data.relationships.likes.data;
          data.some((like, i) => {
            if (like.id === app.session.user.id()) {
              data.splice(i, 1);
              return true;
            }
          });

          if (isLiked) {
            data.unshift({type: 'users', id: app.session.user.id()});
          }
        }
      }, app.translator.trans(isLiked ? 'flarum-likes.forum.post.unlike_link' : 'flarum-likes.forum.post.like_link'))
    );
  });
}
