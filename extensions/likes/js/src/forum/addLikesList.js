import { extend } from 'flarum/extend';
import app from 'flarum/app';
import CommentPost from 'flarum/components/CommentPost';
import Link from 'flarum/components/Link';
import punctuateSeries from 'flarum/helpers/punctuateSeries';
import username from 'flarum/helpers/username';
import icon from 'flarum/helpers/icon';

import PostLikesModal from './components/PostLikesModal';

export default function() {
  extend(CommentPost.prototype, 'footerItems', function(items) {
    const post = this.attrs.post;
    const likes = post.likes();

    if (likes && likes.length) {
      const limit = 4;
      const overLimit = likes.length > limit;

      // Construct a list of names of users who have liked this post. Make sure the
      // current user is first in the list, and cap a maximum of 4 items.
      const names = likes.sort(a => a === app.session.user ? -1 : 1)
        .slice(0, overLimit ? limit - 1 : limit)
        .map(user => {
          return (
            <Link href={app.route.user(user)}>
              {user === app.session.user ? app.translator.trans('flarum-likes.forum.post.you_text') : username(user)}
            </Link>
          );
        });

      // If there are more users that we've run out of room to display, add a "x
      // others" name to the end of the list. Clicking on it will display a modal
      // with a full list of names.
      if (overLimit) {
        const count = likes.length - names.length;

        names.push(
          <a href="#" onclick={e => {
            e.preventDefault();
            app.modal.show(PostLikesModal, {post});
          }}>
            {app.translator.trans('flarum-likes.forum.post.others_link', {count})}
          </a>
        );
      }

      items.add('liked', (
        <div className="Post-likedBy">
          {icon('far fa-thumbs-up')}
          {app.translator.trans('flarum-likes.forum.post.liked_by' + (likes[0] === app.session.user ? '_self' : '') + '_text', {
            count: names.length,
            users: punctuateSeries(names)
          })}
        </div>
      ));
    }
  });
}
