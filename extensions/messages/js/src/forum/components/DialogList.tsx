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
  view() {
    const state = this.attrs.state;

    return (
      <div className="DialogList">
        <ul className="DialogList-list">
          {state.getAllItems().map((dialog) => (
            <DialogListItem
              key={dialog.id()}
              dialog={dialog}
              active={this.attrs.activeDialog?.id() === dialog.id()}
              actions={this.attrs.itemActions}
            />
          ))}
        </ul>
        {state.hasNext() && !this.attrs.hideMore && (
          <div className="DialogList-loadMore">
            <Button className="Button" loading={state.isLoadingNext()} disabled={state.isLoadingNext()} onclick={() => state.loadNext()}>
              {app.translator.trans('flarum-messages.forum.dialog_list.load_more_button')}
            </Button>
          </div>
        )}
      </div>
    );
  }
}
