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

    this.messages = new MessageStreamState({
      filter: {
        dialog: this.attrs.dialog.id(),
      },
      sort: '-createdAt',
    });

    this.messages.refresh();
  }

  view() {
    const recipient = this.attrs.dialog.recipient();

    return (
      <div className="DialogSection">
        <div className="DialogSection-header">
          <Avatar user={recipient} />
          <div className="DialogSection-header-info">
            {(recipient && (
              <Link href={app.route.user(recipient!)}>
                <h2>{username(recipient)}</h2>
              </Link>
            )) || <h2>{username(recipient)}</h2>}
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
