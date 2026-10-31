import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Dialog from '../../common/models/Dialog';
import type Mithril from 'mithril';
import MessageStream from './MessageStream';
import username from 'flarum/common/helpers/username';
import MessageStreamState from '../states/MessageStreamState';
import Avatar from 'flarum/common/components/Avatar';
import Link from 'flarum/common/components/Link';
import app from 'flarum/forum/app';
import ItemList from 'flarum/common/utils/ItemList';
import Button from 'flarum/common/components/Button';
import Dropdown from 'flarum/common/components/Dropdown';
import DetailsModal from './DetailsModal';
import listItems from 'flarum/common/helpers/listItems';
import classList from 'flarum/common/utils/classList';
import extractText from 'flarum/common/utils/extractText';
import leaveDialog from '../utils/leaveDialog';

export interface IDialogStreamAttrs extends ComponentAttrs {
  dialog: Dialog;
  onback?: () => void;
}

export default class DialogSection<CustomAttrs extends IDialogStreamAttrs = IDialogStreamAttrs> extends Component<CustomAttrs> {
  protected loading = false;
  protected messages!: MessageStreamState;
  /**
   * The permalinked message, taken from the route once. The stream drops it
   * from the address after opening on it, and the route's own copy would
   * still say it afterwards.
   */
  protected near: number | null = null;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.near = parseInt(m.route.param('near')) || null;
    this.messages = new MessageStreamState(this.requestParams());

    this.messages.refresh();
  }

  requestParams(): any {
    const params: any = {
      filter: {
        dialog: this.attrs.dialog.id(),
      },
      sort: '-number',
    };

    if (this.near) {
      params.page = { near: this.near };
    }

    return params;
  }

  view() {
    return (
      <div className={classList('DialogSection', { 'DialogSection--group': this.attrs.dialog.isGroup() })}>
        <div className="DialogSection-header">
          {this.headerAvatar()}
          <div className="DialogSection-header-info">{this.headerInfo()}</div>
          <div className="DialogSection-header-actions">{this.actionItems().toArray()}</div>
        </div>
        <MessageStream dialog={this.attrs.dialog} state={this.messages} near={this.near} />
      </div>
    );
  }

  /** The other member's avatar; in a group, the first few of the others'. */
  headerAvatar(): Mithril.Children {
    const dialog = this.attrs.dialog;

    if (dialog.isGroup()) {
      return (
        <div className="DialogSection-header-avatars">
          {dialog
            .recipients()
            .slice(0, 3)
            .map((user) => (
              <Avatar user={user} />
            ))}
        </div>
      );
    }

    return <Avatar user={dialog.recipient()} />;
  }

  /**
   * Who the conversation is with: the other member, with their badges, or a
   * group's title and how many are in it, which opens the list of them.
   */
  headerInfo(): Mithril.Children {
    const dialog = this.attrs.dialog;

    if (dialog.isGroup()) {
      return [
        <h2 className="DialogSection-header-info-title">{dialog.title()}</h2>,
        <Button className="Button Button--link DialogSection-members" onclick={() => app.modal.show(DetailsModal, { dialog })}>
          {app.translator.trans('flarum-messages.forum.dialog_section.members_button', { count: (dialog.users() || []).length })}
        </Button>,
      ];
    }

    const recipient = dialog.recipient();

    return [
      <h2 className="DialogSection-header-info-title">
        {(recipient && <Link href={app.route.user(recipient!)}>{username(recipient)}</Link>) || username(recipient)}
        {!this.recipientCannotReply() ? null : (
          <span className="DialogSection-header-info-helperText">
            {app.translator.trans('flarum-messages.forum.dialog_section.cannot_reply_text')}
          </span>
        )}
      </h2>,
      <div className="badges">{listItems(recipient?.badges().toArray() || [])}</div>,
    ];
  }

  /**
   * Whether to say the other member can't reply: they can't send messages, and
   * the viewer can't send here either. Not to viewers who can't send at all,
   * who aren't told whether anyone else can. Never in a group, where the
   * others still can.
   */
  recipientCannotReply(): boolean {
    if (this.attrs.dialog.isGroup()) return false;

    const recipient = this.attrs.dialog.recipient();

    return !recipient || (app.session.user!.canSendAnyMessage() && !recipient.canSendAnyMessage() && !this.attrs.dialog.canSendMessage());
  }

  actionItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'back',
      <Button className="Button Button--icon DialogSection-back" icon="fas fa-arrow-left" onclick={this.attrs.onback}>
        {app.translator.trans('flarum-messages.forum.dialog_section.back_label')}
      </Button>
    );

    items.add(
      'details',
      <Dropdown
        icon="fas fa-ellipsis-h"
        className="DialogSection-controls"
        buttonClassName="Button Button--icon"
        accessibleToggleLabel={app.translator.trans('flarum-messages.forum.dialog_section.controls_toggle_label')}
        label={app.translator.trans('flarum-messages.forum.dialog_section.controls_toggle_label')}
      >
        {this.controlItems().toArray()}
      </Dropdown>
    );

    return items;
  }

  controlItems() {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'details',
      <Button icon="fas fa-info-circle" onclick={() => app.modal.show(DetailsModal, { dialog: this.attrs.dialog })}>
        {app.translator.trans('flarum-messages.forum.dialog_section.controls.details_button')}
      </Button>
    );

    // Only a group: leaving a direct conversation would leave nobody in it.
    if (this.attrs.dialog.isGroup()) {
      items.add(
        'leave',
        <Button icon="fas fa-sign-out-alt" onclick={() => this.leave()}>
          {app.translator.trans('flarum-messages.forum.dialog_section.controls.leave_button')}
        </Button>,
        -100
      );
    }

    return items;
  }

  /** Leaves the group, once the member has confirmed, and closes it. */
  leave(): Promise<void> {
    if (!confirm(extractText(app.translator.trans('flarum-messages.forum.dialog_section.leave_confirmation')))) {
      return Promise.resolve();
    }

    // A refusal is shown by the request itself.
    return leaveDialog(this.attrs.dialog).then(
      () => (this.attrs.onback ? this.attrs.onback() : m.route.set(app.route('messages'))),
      () => {}
    );
  }
}
