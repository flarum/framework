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

export interface IDialogStreamAttrs extends ComponentAttrs {
  dialog: Dialog;
}

export default class DialogSection<CustomAttrs extends IDialogStreamAttrs = IDialogStreamAttrs> extends Component<CustomAttrs> {
  protected loading = false;
  protected messages!: MessageStreamState;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.messages = new MessageStreamState(this.requestParams());

    this.messages.refresh();
  }

  requestParams(forgetNear = false): any {
    const params: any = {
      filter: {
        dialog: this.attrs.dialog.id(),
      },
      sort: '-number',
    };

    const near = m.route.param('near');

    if (near && !forgetNear) {
      params.page = params.page || {};
      params.page.near = parseInt(near);
    }

    return params;
  }

  view() {
    const recipient = this.attrs.dialog.recipient();

    return (
      <div className="DialogSection">
        <div className="DialogSection-header">
          <Avatar user={recipient} />
          <div className="DialogSection-header-info">
            <h2 className="DialogSection-header-info-title">
              {(recipient && <Link href={app.route.user(recipient!)}>{username(recipient)}</Link>) || username(recipient)}
              {recipient && recipient.canSendAnyMessage() ? null : (
                <span className="DialogSection-header-info-helperText">
                  {app.translator.trans('flarum-messages.forum.dialog_section.cannot_reply_text')}
                </span>
              )}
            </h2>
            <div className="badges">{listItems(recipient?.badges().toArray() || [])}</div>
          </div>
          <div className="DialogSection-header-actions">{this.actionItems().toArray()}</div>
        </div>
        <MessageStream dialog={this.attrs.dialog} state={this.messages} />
      </div>
    );
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

    return items;
  }
}
