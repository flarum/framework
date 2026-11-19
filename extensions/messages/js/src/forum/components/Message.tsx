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
import MessageControls from '../utils/MessageControls';
import type MessageStreamState from '../states/MessageStreamState';
import Icon from 'flarum/common/components/Icon';
import listItems from 'flarum/common/helpers/listItems';
import MessageEdited from './MessageEdited';

export interface IMessageAttrs extends IAbstractPostAttrs {
  message: DialogMessage;
  state: MessageStreamState;
}

/**
 * The `Post` component displays a single post. The basic post template just
 * includes a controls dropdown; subclasses must implement `content` and `attrs`
 * methods.
 */
export default abstract class Message<CustomAttrs extends IMessageAttrs = IMessageAttrs> extends AbstractPost<CustomAttrs> {
  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    // Opening the composer to edit it changes nothing on the message itself.
    this.subtree.check(() => this.isEditing());
  }

  user(): User | null | false {
    return this.attrs.message.user();
  }

  controls(): Mithril.Children[] {
    return MessageControls.controls(this.attrs.message, this).toArray();
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
      'Message--hidden': message.isHidden(),
      revealContent: false,
      editing: this.isEditing(),
    });

    if (this.isEditing()) attrs['aria-busy'] = 'true';

    return attrs;
  }

  header(): Mithril.Children {
    return super.header();
  }

  /** Whether this message is open in the composer to be edited. */
  isEditing(): boolean {
    return app.composer.bodyMatches('ext:flarum/messages/forum/components/EditMessageComposer', { message: this.attrs.message });
  }

  content(): Mithril.Children[] {
    const message = this.attrs.message;

    // Deleted, in a way that can be undone: the others see that it was;
    // whoever may restore it sees what it said, set apart.
    if (message.isHidden()) {
      const contentHtml = message.canHide() || message.user() === app.session.user ? message.contentHtml() : null;

      return super.content().concat([
        <header className="Post-header">
          <ul>{listItems(this.headerItems().toArray())}</ul>
        </header>,
        <div className="Post-body">
          <p className="Message-deletedNotice">
            <Icon name="far fa-trash-alt" /> {app.translator.trans('flarum-messages.forum.message.deleted_text')}
          </p>
          {contentHtml ? <div className="Message-deletedContent">{m.trust(contentHtml)}</div> : null}
        </div>,
      ]);
    }

    return super
      .content()
      .concat([
        <Comment
          headerItems={this.headerItems()}
          cardVisible={false}
          isEditing={this.isEditing()}
          isHidden={false}
          contentHtml={message.contentHtml() || ''}
          user={message.user()}
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
    return this.attrs.message.user() ? <Avatar user={this.attrs.message.user()} className="Post-avatar" /> : '';
  }

  headerItems() {
    const items = new ItemList<Mithril.Children>();
    const message = this.attrs.message;

    items.add('user', <PostUser post={message} headingLevel={3} />, 100);
    items.add(
      'meta',
      <PostMeta
        post={message}
        permalink={() => {
          const dialog = message.dialog();

          if (!dialog) {
            return null;
          }

          return app.forum.attribute('baseOrigin') + app.route.dialog(dialog, message.number());
        }}
      />
    );

    if (message.editedAt() && !message.isHidden()) {
      items.add('edited', <MessageEdited message={message} />);
    }

    return items;
  }
}
