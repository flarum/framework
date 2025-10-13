import app from '../../forum/app';
import Component, { type ComponentAttrs } from '../../common/Component';
import Link from '../../common/components/Link';
import username from '../../common/helpers/username';
import userOnline from '../../common/helpers/userOnline';
import listItems from '../../common/helpers/listItems';
import Avatar from '../../common/components/Avatar';
import type Model from '../../common/Model';
import type Post from '../../common/models/Post';
import type User from '../../common/models/User';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';

export interface IPostUserAttrs extends ComponentAttrs {
  /** Can be a post or similar model like private message */
  post: Post | (Model & { user: () => User | null | false });
}

/**
 * The `PostUser` component shows the avatar and username of a post's author.
 */
export default class PostUser<CustomAttrs extends IPostUserAttrs = IPostUserAttrs> extends Component<CustomAttrs> {
  view() {
    const post = this.attrs.post;
    const user = post.user();

    const items = user ? this.userViewItems(user, post) : this.noUserViewItems(user, post);

    return <div className="PostUser">{items.toArray()}</div>;
  }

  noUserViewItems(user: false | User | null, post: Post | (Model & { user: () => false | User | null })): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'postUser-name',
      <h3 className="PostUser-name">
        <Avatar user={user} className="Post-avatar" /> {username(user)}
      </h3>,
      100
    );

    return items;
  }

  userViewItems(user: User, post: Post | (Model & { user: () => false | User | null })): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'postUser-name',
      <h3 className="PostUser-name">
        <Link href={app.route.user(user)}>{this.linkChildren(user).toArray()}</Link>
      </h3>,
      100
    );

    items.add('postUser-badges', <ul className="PostUser-badges badges badges--packed">{listItems(user.badges().toArray())}</ul>, 90);

    return items;
  }

  linkChildren(user: User): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add('avatar', <Avatar user={user} className="Post-avatar" />, 100);

    const onlineIndicator = userOnline(user);
    if (onlineIndicator !== null) {
      items.add('userOnline', onlineIndicator, 90);
    }

    items.add('username', username(user), 80);

    return items;
  }
}
