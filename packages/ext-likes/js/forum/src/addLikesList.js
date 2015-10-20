import { extend } from 'flarum/extend';
import app from 'flarum/app';
import CommentPost from 'flarum/components/CommentPost';
import punctuateSeries from 'flarum/helpers/punctuateSeries';
import username from 'flarum/helpers/username';
import icon from 'flarum/helpers/icon';

import PostLikesModal from 'flarum/likes/components/PostLikesModal';

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
              {user === app.session.user ? app.translator.trans('flarum-likes.forum.you') : username(user)}
            </a>
          );
        });

      // If there are more users that we've run out of room to display, add a "x
      // others" name to the end of the list. Clicking on it will display a modal
      // with a full list of names.
      if (likes.length > limit) {
        const count = likes.length - limit;

        names.push(
          <a href="#" onclick={e => {
            e.preventDefault();
            app.modal.show(new PostLikesModal({post}));
          }}>
            {app.translator.transChoice('flarum-likes.forum.others', count, {count})}
          </a>
        );
      }

      items.add('liked', (
        <div className="Post-likedBy">
          {icon('thumbs-o-up')}
          {app.translator.transChoice('flarum-likes.forum.post_liked_by' + (likes[0] === app.session.user ? '_self' : ''), names.length, {
            count: names.length,
            users: punctuateSeries(names)
          })}
        </div>
      ));
    }
  });
}
