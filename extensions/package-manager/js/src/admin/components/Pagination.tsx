import app from 'flarum/admin/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import QueueState from '../states/QueueState';

interface PaginationAttrs extends ComponentAttrs {
  list: QueueState;
}

/**
 * @todo make it abstract in core for reusability.
 */
export default class Pagination extends Component<PaginationAttrs> {
  view() {
    return (
      <nav className="Pagination UserListPage-gridPagination">
        <Button
          disabled={!this.attrs.list.hasPrev() || app.extensionManager.control.isLoading()}
          title={app.translator.trans('core.admin.users.pagination.back_button')}
          onclick={() => this.attrs.list.prev()}
          icon="fas fa-chevron-left"
          className="Button Button--icon UserListPage-backBtn"
        />
        <span className="UserListPage-pageNumber">
          {app.translator.trans('core.admin.users.pagination.page_counter', {
            current: this.attrs.list.pageNumber() + 1,
            total: this.attrs.list.getTotalPages(),
          })}
        </span>
        <Button
          disabled={!this.attrs.list.hasNext() || app.extensionManager.control.isLoading()}
          title={app.translator.trans('core.admin.users.pagination.next_button')}
          onclick={() => this.attrs.list.next()}
          icon="fas fa-chevron-right"
          className="Button Button--icon UserListPage-nextBtn"
        />
      </nav>
    );
  }
}
