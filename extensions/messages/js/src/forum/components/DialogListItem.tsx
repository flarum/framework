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
import { ModelIdentifier } from 'flarum/common/Model';

export interface IDialogListItemAttrs extends ComponentAttrs {
  dialog: Dialog;
  active?: boolean;
  actions?: boolean;
}

export default class DialogListItem<CustomAttrs extends IDialogListItemAttrs = IDialogListItemAttrs> extends Component<CustomAttrs> {
  view(vnode: Mithril.Vnode<CustomAttrs, this>) {
    const dialog = this.attrs.dialog;

    const recipient = dialog.recipient();
    const lastMessage = dialog.lastMessage();

    return (
      <li
        className={classList('DialogListItem', {
          'DialogListItem--unread': dialog.unreadCount(),
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
            {!!dialog.unreadCount() && <div className="Bubble Bubble--primary">{dialog.unreadCount()}</div>}
          </div>
          <div className="DialogListItem-content">
            <div className="DialogListItem-title">
              {username(recipient)}
              {humanTime(dialog.lastMessageAt()!)}
              {this.attrs.actions && <div className="DialogListItem-actions">{this.actionItems().toArray()}</div>}
            </div>
            <div className="DialogListItem-lastMessage">{lastMessage ? lastMessage.contentPlain()?.slice(0, 80) : ''}</div>
          </div>
        </Link>
      </li>
    );
  }

  actionItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'markAsRead',
      <Button
        className="Notification-action Button Button--link"
        icon="fas fa-check"
        aria-label={app.translator.trans('flarum-messages.forum.dialog_list.mark_as_read_tooltip')}
        onclick={(e: Event) => {
          e.preventDefault();
          e.stopPropagation();

          this.attrs.dialog
            .save({ lastReadMessageId: (this.attrs.dialog.data.relationships?.lastMessage.data as ModelIdentifier).id })
            .finally(() => {
              if (this.attrs.dialog.unreadCount() === 0) {
                app.session.user!.pushAttributes({
                  messageCount: (app.session.user!.attribute<number>('messageCount') ?? 1) - 1,
                });
              }

              m.redraw();
            });
        }}
      />,
      100
    );

    return items;
  }
}
