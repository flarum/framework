import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Dialog from '../../common/models/Dialog';
import type Mithril from 'mithril';
import MessageStream from './MessageStream';
import username from 'flarum/common/helpers/username';
import MessageStreamState from '../states/MessageStreamState';
import Avatar from 'flarum/common/components/Avatar';
import Link from 'flarum/common/components/Link';
import app from 'flarum/forum/app';

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
    return (
      <div className="DialogSection">
        <div className="DialogSection-header">
          <Avatar user={this.attrs.dialog.recipient()} />
          {(this.attrs.dialog.recipient() && (
            <Link href={app.route.user(this.attrs.dialog.recipient()!)}>
              <h2>{username(this.attrs.dialog.recipient())}</h2>
            </Link>
          )) || <h2>{username(this.attrs.dialog.recipient())}</h2>}
        </div>
        <MessageStream dialog={this.attrs.dialog} state={this.messages} />
      </div>
    );
  }
}
