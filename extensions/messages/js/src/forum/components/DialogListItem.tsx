import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Mithril from 'mithril';
import classList from 'flarum/common/utils/classList';
import Link from 'flarum/common/components/Link';
import app from 'flarum/forum/app';
import Avatar from 'flarum/common/components/Avatar';
import username from 'flarum/common/helpers/username';
import humanTime from 'flarum/common/helpers/humanTime';
import ItemList from 'flarum/common/utils/ItemList';
import Button from 'flarum/common/components/Button';
import type Dialog from '../../common/models/Dialog';
import { markRead } from '../utils/readState';

export interface IDialogListItemAttrs extends ComponentAttrs {
  dialog: Dialog;
  active?: boolean;
  actions?: boolean;
}

export default class DialogListItem<CustomAttrs extends IDialogListItemAttrs = IDialogListItemAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const dialog = this.attrs.dialog;

    const unread = dialog.unreadCount();
    const actions = this.attrs.actions ? this.actionItems().toArray() : [];

    return (
      <li
        className={classList('DialogListItem', {
          'DialogListItem--unread': unread,
          'DialogListItem--actions': actions.length,
          active: this.attrs.active,
        })}
      >
        <Link
          href={app.route.dialog(dialog)}
          className={classList('DialogListItem-button', {
            active: this.attrs.active,
          })}
        >
          <div className={classList('DialogListItem-avatar', { 'DialogListItem-avatar--group': dialog.isGroup() })}>
            {this.avatar()}
            {!!unread && (
              <div className="Bubble Bubble--primary">
                <span aria-hidden="true">{unread}</span>
                <span className="sr-only">{app.translator.trans('flarum-messages.forum.dialog_list.unread_count_text', { count: unread })}</span>
              </div>
            )}
          </div>
          <div className="DialogListItem-content">
            <div className="DialogListItem-title">
              {this.name()}
              {humanTime(dialog.lastMessageAt()!)}
            </div>
            <div className="DialogListItem-lastMessage">{this.preview()}</div>
          </div>
        </Link>
        {/* Beside the link, not inside it: a button nested in a link is neither to a browser nor a screen reader. */}
        {!!actions.length && <div className="DialogListItem-actions">{actions}</div>}
      </li>
    );
  }

  /** The other member's avatar; in a group, two of the others', one over the other. */
  avatar(): Mithril.Children {
    const dialog = this.attrs.dialog;

    if (dialog.isGroup()) {
      return dialog
        .recipients()
        .slice(0, 2)
        .map((user) => <Avatar user={user} />);
    }

    return <Avatar user={dialog.recipient()} />;
  }

  /** The other member; a group goes by its title, which names its members. */
  name(): Mithril.Children {
    const dialog = this.attrs.dialog;

    return dialog.isGroup() ? <span className="DialogListItem-name">{dialog.title()}</span> : username(dialog.recipient());
  }

  /**
   * The last message, said to be the member's own when it is. In a group,
   * where it could be from any of the others, who wrote it.
   */
  preview(): Mithril.Children {
    const lastMessage = this.attrs.dialog.lastMessage();

    if (!lastMessage) return '';

    const content = lastMessage.contentPlain()?.slice(0, 80) ?? '';

    if (lastMessage.user() === app.session.user) {
      return app.translator.trans('flarum-messages.forum.dialog_list.own_last_message_preview', { content });
    }

    if (this.attrs.dialog.isGroup()) {
      return app.translator.trans('flarum-messages.forum.dialog_list.group_last_message_preview', {
        username: username(lastMessage.user() || null),
        content,
      });
    }

    return content;
  }

  actionItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();
    const dialog = this.attrs.dialog;
    const lastMessageId = dialog.messageRelationshipId('lastMessage');

    // Nothing to mark on a dialog that is read, or one with no messages.
    if (dialog.unreadCount() && lastMessageId) {
      items.add(
        'markAsRead',
        <Button
          className="Notification-action Button Button--link"
          icon="fas fa-check"
          aria-label={app.translator.trans('flarum-messages.forum.dialog_list.mark_as_read_tooltip')}
          onclick={() => {
            markRead(dialog, Number(lastMessageId))
              .catch(() => {})
              .finally(() => m.redraw());
          }}
        />,
        100
      );
    }

    return items;
  }
}
