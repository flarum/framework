import { extend } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import UserPage from 'flarum/forum/components/UserPage';
import LinkButton from 'flarum/common/components/LinkButton';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

export default function addLikesTabToUserProfile() {
  extend(UserPage.prototype, 'navItems', function (items: ItemList<Mithril.Children>) {
    const user = this.user;
    // The API only sends the count to the user themselves and to moderators,
    // so most viewers have no number to show.
    const likeCount = user?.likeCount();

    items.add(
      'likes',
      <LinkButton href={app.route('user.likes', { username: user?.slug() })} icon="far fa-thumbs-up">
        {app.translator.trans('flarum-likes.forum.user.likes_link')}{' '}
        {typeof likeCount === 'number' ? <span className="Button-badge">{likeCount}</span> : null}
      </LinkButton>,
      88
    );
  });
}
