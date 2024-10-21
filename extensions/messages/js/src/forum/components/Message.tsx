import app from 'flarum/forum/app';
import ItemList from 'flarum/common/utils/ItemList';
import Mithril from 'mithril';
import AbstractPost, { type IAbstractPostAttrs } from 'flarum/forum/components/AbstractPost';
import type User from 'flarum/common/models/User';
import DialogMessage from '../../common/models/DialogMessage';
import Avatar from 'flarum/common/components/Avatar';
import Comment from 'flarum/forum/components/Comment';
import PostUser from 'flarum/forum/components/PostUser';
import PostMeta from 'flarum/forum/components/PostMeta';
import classList from 'flarum/common/utils/classList';

export interface IMessageAttrs extends IAbstractPostAttrs {
  message: DialogMessage;
}

/**
 * The `Post` component displays a single post. The basic post template just
 * includes a controls dropdown; subclasses must implement `content` and `attrs`
 * methods.
 */
export default abstract class Message<CustomAttrs extends IMessageAttrs = IMessageAttrs> extends AbstractPost<CustomAttrs> {
  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);
  }

  user(): User | null | false {
    return this.attrs.message.user();
  }

  controls(): Mithril.Children[] {
    return [];
  }

  freshness(): Date {
    return this.attrs.message.freshness;
  }

  createdByStarter(): boolean {
    return false;
  }

  onbeforeupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    return super.onbeforeupdate(vnode);
  }

  onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onupdate(vnode);
  }

  elementAttrs() {
    const message = this.attrs.message;
    const attrs = super.elementAttrs();

    attrs.className = classList(attrs.className || null, 'Message', {
      'Post--renderFailed': message.renderFailed(),
      revealContent: false,
      editing: false,
    });

    return attrs;
  }

  header(): Mithril.Children {
    return super.header();
  }

  content(): Mithril.Children[] {
    return super
      .content()
      .concat([
        <Comment
          headerItems={this.headerItems()}
          cardVisible={false}
          isEditing={false}
          isHidden={false}
          contentHtml={this.attrs.message.contentHtml()}
          user={this.attrs.message.user()}
        />,
      ]);
  }

  classes(existing?: string): string[] {
    return super.classes(existing);
  }

  actionItems(): ItemList<Mithril.Children> {
    return super.actionItems();
  }

  footerItems(): ItemList<Mithril.Children> {
    return super.footerItems();
  }

  sideItems(): ItemList<Mithril.Children> {
    return super.sideItems();
  }

  avatar(): Mithril.Children {
    return this.attrs.message.user() ? <Avatar user={this.attrs.message.user()} /> : '';
  }

  headerItems() {
    const items = new ItemList<Mithril.Children>();
    const message = this.attrs.message;

    items.add('user', <PostUser post={message} />, 100);
    items.add('meta', <PostMeta post={message} />);

    return items;
  }
}
