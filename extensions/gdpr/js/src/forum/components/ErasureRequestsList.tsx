import app from 'flarum/forum/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Avatar from 'flarum/common/components/Avatar';
import username from 'flarum/common/helpers/username';
import HeaderList from 'flarum/forum/components/HeaderList';
import HeaderListItem from 'flarum/forum/components/HeaderListItem';
import type { Page } from 'flarum/common/states/PaginatedListState';
import ItemList from 'flarum/common/utils/ItemList';

import ProcessErasureRequestModal from './ProcessErasureRequestModal';
import type ErasureRequestsListState from '../states/ErasureRequestsListState';
import type ErasureRequest from '../../common/models/ErasureRequest';

export interface IErasureRequestsListAttrs extends ComponentAttrs {
  state: ErasureRequestsListState;
}

export default class ErasureRequestsList<CustomAttrs extends IErasureRequestsListAttrs = IErasureRequestsListAttrs> extends Component<CustomAttrs> {
  view() {
    const state = this.attrs.state;

    return (
      <HeaderList
        className="ErasureRequestsList"
        title={app.translator.trans('flarum-gdpr.forum.erasure_requests.title')}
        controls={this.controlItems()}
        hasItems={state.hasItems()}
        loading={state.isLoading()}
        emptyText={app.translator.trans('flarum-gdpr.forum.erasure_requests.empty_text')}
        loadMore={() => state.hasNext() && !state.isLoadingNext() && state.loadNext()}
      >
        <ul className="HeaderListGroup-content">{this.content(state)}</ul>
      </HeaderList>
    );
  }

  showModal(request: ErasureRequest) {
    app.modal.show(ProcessErasureRequestModal as any, { request });
  }

  controlItems() {
    const items = new ItemList();

    return items;
  }

  content(state: ErasureRequestsListState) {
    if (!state.isLoading() && state.hasItems()) {
      return state.getPages().map((page: Page<ErasureRequest>) => {
        return page.items.map((request: ErasureRequest, index) => {
          return (
            <li>
              <HeaderListItem
                key={index}
                className="EraseRequest"
                onclick={this.showModal.bind(this, request)}
                avatar={<Avatar user={request.user()} />}
                icon="fas fa-user-edit"
                content={app.translator.trans(`flarum-gdpr.forum.erasure_requests.item_text`, {
                  name: username(request.user()),
                })}
                datetime={request.createdAt()}
                excerpt=""
              />
            </li>
          );
        });
      });
    }

    return null;
  }
}
