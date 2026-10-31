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

    const recipient = dialog.recipient();
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
          <div className="DialogListItem-avatar">
            <Avatar user={recipient} />
            {!!unread && (
              <div className="Bubble Bubble--primary">
                <span aria-hidden="true">{unread}</span>
                <span className="sr-only">{app.translator.trans('flarum-messages.forum.dialog_list.unread_count_text', { count: unread })}</span>
              </div>
            )}
          </div>
          <div className="DialogListItem-content">
            <div className="DialogListItem-title">
              {username(recipient)}
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

  /** The last message, said to be the member's own when it is. */
  preview(): Mithril.Children {
    const lastMessage = this.attrs.dialog.lastMessage();

    if (!lastMessage) return '';

    const content = lastMessage.contentPlain()?.slice(0, 80) ?? '';

    return lastMessage.user() === app.session.user
      ? app.translator.trans('flarum-messages.forum.dialog_list.own_last_message_preview', { content })
      : content;
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
