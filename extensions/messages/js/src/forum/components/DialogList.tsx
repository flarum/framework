import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import DialogListState from '../states/DialogListState';
import Dialog from '../../common/models/Dialog';
import Button from 'flarum/common/components/Button';
import DialogListItem from './DialogListItem';

export interface IDialogListAttrs extends ComponentAttrs {
  state: DialogListState;
  activeDialog?: Dialog | null;
  hideMore?: boolean;
  itemActions?: boolean;
}

export default class DialogList<CustomAttrs extends IDialogListAttrs = IDialogListAttrs> extends Component<CustomAttrs> {
  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);
  }

  oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.oncreate(vnode);
  }

  onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>) {
    super.onupdate(vnode);
  }

  view() {
    return (
      <div className="DialogList">
        <ul className="DialogList-list">
          {this.attrs.state.getAllItems().map((dialog) => (
            <DialogListItem dialog={dialog} active={this.attrs.activeDialog?.id() === dialog.id()} actions={this.attrs.itemActions} />
          ))}
        </ul>
        {this.attrs.state.hasNext() && !this.attrs.hideMore && (
          <div className="DialogList-loadMore">
            <Button className="Button" onclick={this.attrs.state.loadNext.bind(this.attrs.state)}>
              {app.translator.trans('flarum-messages.forum.dialog_list.load_more_button')}
            </Button>
          </div>
        )}
      </div>
    );
  }
}
