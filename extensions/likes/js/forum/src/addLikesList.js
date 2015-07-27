import { extend } from 'flarum/extend';
import app from 'flarum/app';
import CommentPost from 'flarum/components/CommentPost';
import punctuate from 'flarum/helpers/punctuate';
import username from 'flarum/helpers/username';
import icon from 'flarum/helpers/icon';

import PostLikesModal from 'likes/components/PostLikesModal';

export default function() {
  extend(CommentPost.prototype, 'footerItems', function(items) {
    const post = this.props.post;
    const likes = post.likes();

    if (likes && likes.length) {
      const limit = 3;

      // Construct a list of names of users who have like this post. Make sure the
      // current user is first in the list, and cap a maximum of 3 names.
      const names = likes.sort(a => a === app.session.user ? -1 : 1)
        .slice(0, limit)
        .map(user => {
          return (
            <a href={app.route.user(user)} config={m.route}>
              {user === app.session.user ? app.trans('likes.you') : username(user)}
            </a>
          );
        });

      // If there are more users that we've run out of room to display, add a "x
      // others" name to the end of the list. Clicking on it will display a modal
      // with a full list of names.
      if (likes.length > limit) {
        names.push(
          <a href="#" onclick={e => {
            e.preventDefault();
            app.modal.show(new PostLikesModal({post}));
          }}>
            {app.trans('likes.others', {count: likes.length - limit})}
          </a>
        );
      }

      items.add('liked', (
        <div className="Post-likedBy">
          {icon('thumbs-o-up')}
          {app.trans('likes.post_liked_by' + (likes[0] === app.session.user ? '_self' : ''), {
            count: names.length,
            users: punctuate(names)
          })}
        </div>
      ));
    }
  });
}
