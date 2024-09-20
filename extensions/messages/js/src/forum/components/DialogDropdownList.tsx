import app from 'flarum/forum/app';
import Component from 'flarum/common/Component';
import type { ComponentAttrs } from 'flarum/common/Component';
import HeaderList from 'flarum/forum/components/HeaderList';
import type Mithril from 'mithril';
import DialogListState from '../states/DialogListState';
import DialogList from './DialogList';
import LinkButton from 'flarum/common/components/LinkButton';
import ItemList from 'flarum/common/utils/ItemList';
import Tooltip from 'flarum/common/components/Tooltip';
import Button from 'flarum/common/components/Button';

export interface IDialogListDropdownAttrs extends ComponentAttrs {
  state: DialogListState;
}

export default class DialogDropdownList<CustomAttrs extends IDialogListDropdownAttrs = IDialogListDropdownAttrs> extends Component<
  CustomAttrs,
  DialogListState
> {
  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);
  }

  view() {
    const state = this.attrs.state;

    return (
      <HeaderList
        className="DialogDropdownList"
        title={app.translator.trans('flarum-messages.forum.dialog_list.title')}
        controls={this.controlItems()}
        hasItems={state.hasItems()}
        loading={state.isLoading()}
        emptyText={app.translator.trans('flarum-messages.forum.messages_page.empty_text')}
        loadMore={() => state.hasNext() && !state.isLoadingNext() && state.loadNext()}
        footer={() => (
          <h4>
            <LinkButton href={app.route('messages')} className="Button Button--link" icon="fas fa-inbox">
              {app.translator.trans('flarum-messages.forum.dialog_list.view_all')}
            </LinkButton>
          </h4>
        )}
      >
        <div className="HeaderListGroup-content">{this.content()}</div>
      </HeaderList>
    );
  }

  controlItems() {
    const items = new ItemList();
    const state = this.attrs.state;

    if (app.session.user!.attribute<number>('messageCount') > 0) {
      items.add(
        'mark_all_as_read',
        <Tooltip text={app.translator.trans('flarum-messages.forum.messages_page.mark_all_as_read_tooltip')}>
          <Button
            className="Button Button--link"
            data-container=".DialogDropdownList"
            icon="fas fa-check"
            title={app.translator.trans('flarum-messages.forum.messages_page.mark_all_as_read_tooltip')}
            onclick={state.markAllAsRead.bind(state)}
          />
        </Tooltip>,
        70
      );
    }

    return items;
  }

  content() {
    return <DialogList state={this.attrs.state} hideMore={true} itemActions={true} />;
  }
}
