import app from '../../forum/app';
import Component from '../../common/Component';
import Link from '../../common/components/Link';
import UserCard from './UserCard';
import avatar from '../../common/helpers/avatar';
import username from '../../common/helpers/username';
import userOnline from '../../common/helpers/userOnline';
import listItems from '../../common/helpers/listItems';
import ItemList from '../../common/utils/ItemList';

/**
 * The `PostUser` component shows the avatar and username of a post's author.
 *
 * ### Attrs
 *
 * - `post`
 */
export default class PostUser extends Component {
  view() {
    const post = this.attrs.post;
    const user = post.user();

    const items = user ? this.userViewItems(user, post) : this.noUserViewItems(user);

    return <div className="PostUser">{items.toArray()}</div>;
  }

  noUserViewItems(user) {
    const items = new ItemList();

    items.add(
      'postUser-name',
      <h3 className="PostUser-name">
        {avatar(user, { className: 'PostUser-avatar' })} {username(user)}
      </h3>,
      100
    );

    return items;
  }

  userViewItems(user, post) {
    const items = new ItemList();

    items.add(
      'postUser-name',
      <h3 className="PostUser-name">
        <Link href={app.route.user(user)}>{this.linkChildren(user).toArray()}</Link>
      </h3>,
      100
    );

    items.add('postUser-badges', <ul className="PostUser-badges badges">{listItems(user.badges().toArray())}</ul>, 90);

    if (!post.isHidden() && this.attrs.cardVisible) {
      items.add(
        'postUser-card',
        <UserCard user={user} className="UserCard--popover" controlsButtonClassName="Button Button--icon Button--flat" />,
        80
      );
    }

    return items;
  }

  linkChildren(user) {
    const items = new ItemList();

    items.add('avatar', avatar(user, { className: 'PostUser-avatar' }), 100);

    const onlineIndicator = userOnline(user);
    if (onlineIndicator !== null) {
      items.add('userOnline', onlineIndicator, 90);
    }

    items.add('username', username(user), 80);

    return items;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    let timeout;

    this.$()
      .on('mouseover', '.PostUser-name a, .UserCard', () => {
        clearTimeout(timeout);
        timeout = setTimeout(this.showCard.bind(this), 500);
      })
      .on('mouseout', '.PostUser-name a, .UserCard', () => {
        clearTimeout(timeout);
        timeout = setTimeout(this.hideCard.bind(this), 250);
      });
  }

  /**
   * Show the user card.
   */
  showCard() {
    this.attrs.oncardshow();

    setTimeout(() => this.$('.UserCard').addClass('in'));
  }

  /**
   * Hide the user card.
   */
  hideCard() {
    this.$('.UserCard')
      .removeClass('in')
      .one('transitionend webkitTransitionEnd oTransitionEnd', () => {
        this.attrs.oncardhide();
      });
  }
}
