import app from '../../forum/app';
import Component, { type ComponentAttrs } from '../../common/Component';
import Link from '../../common/components/Link';
import username from '../../common/helpers/username';
import userOnline from '../../common/helpers/userOnline';
import listItems from '../../common/helpers/listItems';
import Avatar from '../../common/components/Avatar';
import Tooltip from '../../common/components/Tooltip';
import Post from '../../common/models/Post';
import type Model from '../../common/Model';
import type User from '../../common/models/User';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';

export interface IPostUserAttrs extends ComponentAttrs {
  /** Can be a post or similar model like private message */
  post: Post | (Model & { user: () => User | null | false });
  /**
   * The heading level of the author's name. Defaults to 2, which follows the
   * discussion title. Pass 3 where the author sits below a heading of its own,
   * such as a conversation title.
   */
  headingLevel?: 2 | 3;
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

  protected headingTag(): 'h2' | 'h3' {
    return this.attrs.headingLevel === 3 ? 'h3' : 'h2';
  }

  noUserViewItems(user: false | User | null, post: Post | (Model & { user: () => false | User | null })): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const Heading = this.headingTag();

    items.add(
      'postUser-name',
      <Heading className="PostUser-name">
        <Avatar user={user} className="Post-avatar" /> {username(user)}
      </Heading>,
      100
    );

    const source = this.sourceView(post);
    if (source) {
      items.add('postUser-source', source, 95);
    }

    return items;
  }

  userViewItems(user: User, post: Post | (Model & { user: () => false | User | null })): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const Heading = this.headingTag();

    items.add(
      'postUser-name',
      <Heading className="PostUser-name">
        <Link href={app.route.user(user)}>{this.linkChildren(user).toArray()}</Link>
      </Heading>,
      100
    );

    const source = this.sourceView(post);
    if (source) {
      items.add('postUser-source', source, 95);
    }

    items.add('postUser-badges', <ul className="PostUser-badges badges badges--packed">{listItems(user.badges().toArray())}</ul>, 90);

    return items;
  }

  /**
   * The "via …" byline for a post made outside the forum's own interface, or
   * null for one made in it.
   */
  sourceView(post: IPostUserAttrs['post']): Mithril.Children {
    if (!(post instanceof Post) || !post.source()) return null;

    const label = this.sourceLabel(post);
    if (!label) return null;

    const byline = <span className="PostUser-source">{app.translator.trans('core.forum.post.source_text', { source: label })}</span>;
    const tooltip = this.sourceTooltip(post);

    return tooltip ? <Tooltip text={tooltip}>{byline}</Tooltip> : byline;
  }

  /**
   * What follows "via": the name stored with the post, else the source's own
   * label. Extensions can label their own sources here.
   */
  sourceLabel(post: Post): string | null {
    const name = post.sourceName();
    if (name) return name;

    if (post.source() === 'api') return app.translator.trans('core.forum.post.source_api_label', {}, true);

    return post.source() ?? null;
  }

  /**
   * More about the source, shown on hover. Extensions can describe their own
   * sources here.
   */
  sourceTooltip(post: Post): string | null {
    if (post.source() === 'api') return app.translator.trans('core.forum.post.source_api_tooltip', {}, true);

    return app.translator.trans('core.forum.post.source_app_tooltip', {}, true);
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
