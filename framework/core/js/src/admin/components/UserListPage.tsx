import Mithril from 'mithril';

import app from '../../admin/app';

import Button from '../../common/components/Button';
import Dropdown from '../../common/components/Dropdown';

import listItems from '../../common/helpers/listItems';

import type User from '../../common/models/User';
import type { IPageAttrs } from '../../common/components/Page';
import type ModalManagerState from '../../common/states/ModalManagerState';

import ItemList from '../../common/utils/ItemList';
import classList from '../../common/utils/classList';
import extractText from '../../common/utils/extractText';
import AdminPage from './AdminPage';
import { debounce } from '../../common/utils/throttleDebounce';
import CreateUserModal from './CreateUserModal';
import Icon from '../../common/components/Icon';
import Input from '../../common/components/Input';
import GambitsAutocompleteDropdown from '../../common/components/GambitsAutocompleteDropdown';
import type { BulkActionContext, BulkSummary } from '../../common/resource/BulkRunner';
import { renderColumn } from '../../common/resource/field';
import RelationInput from '../../common/resource/inputs/RelationInput';
import ResourceDefinition from '../../common/resource/ResourceDefinition';
import ResourceListState from '../../common/resource/ResourceListState';
import type { Field } from '../../common/resource/types';
import ResourceTable, { type ColumnData as ResourceColumnData, type ResourceTableAttrs } from './resource/ResourceTable';
import ResourceDeleteModal from './resource/ResourceDeleteModal';

/**
 * One user list column. `{name, content}` is all an extension needs; the other keys (`sort`,
 * `className`, `attrs`, `toggleable`, `hidden`, `csv`) are `ResourceTable`'s.
 */
export type ColumnData = ResourceColumnData<User>;

/**
 * What `createModal()` / `editModal()` return: anything `app.modal.show()` takes, a modal class or a
 * loader of one.
 *
 * @experimental
 */
export type UserListModal = Parameters<ModalManagerState['show']>[0];

// Core's groups have no fulltext searcher, so relation pickers over them (the users form's groups,
// the list's group filter) render without a search box.
RelationInput.UNSEARCHABLE_TYPES.add('groups');

/**
 * Descriptor fields the page already shows under another column key; they get no inferred column.
 */
const FIELD_COLUMNS: Record<string, string> = {
  username: 'username',
  displayName: 'displayName',
  joinTime: 'joinDate',
  groups: 'groupBadges',
};

/**
 * `EditUserModal` stays a lazily loaded chunk, as it was before `editModal()` existed.
 */
const loadEditUserModal = () => import('../../common/components/EditUserModal');

interface UserListTableAttrs extends ResourceTableAttrs<User> {
  bulkActions: (context: BulkActionContext<User>) => ItemList<Mithril.Children>;
  /** Called with the number of users a bulk action deleted, before its summary shows. */
  onBulkDeleted: (count: number) => void;
  /** The page's `headerItems()`, rendered into the table's toolbar. */
  headerItems: () => ItemList<Mithril.Children>;
}

/**
 * The user list's table. The page's `headerItems()` render in the table's toolbar, in priority
 * order, split at `totalUsers` (priority 90 unless an extension moves it):
 *
 * - `search` (the gambit search) replaces the table's search in the left view group;
 * - every other item ranked above `totalUsers` renders in the view group too, at its own priority
 *   (`header-{key}`): an item between `search` (100) and `totalUsers` (90) right after the search,
 *   an item above `search` before it. The table's filters (priority 80 and below) follow;
 * - `totalUsers` fills the count slot of the right actions group;
 * - the items ranked at or below `totalUsers` (`actions`: "New User", and any an extension adds)
 *   follow the count there, replacing the table's refresh/create.
 *
 * The sort is the column headers, and the `userActions` column is the actions column, so the table
 * leaves out its own. Cells keep the `UserListPage-grid-*` classes and `data-user-id`. A bulk
 * action's deleted users lower the user total.
 */
class UserListTable extends ResourceTable<User, UserListTableAttrs> {
  /** `headerItems()` of the toolbar pass in progress, so it is built once per redraw. */
  protected header: ItemList<Mithril.Children> | null = null;

  /** The priority splitting the header between the view group and the actions group. */
  protected headerSplit(header: ItemList<Mithril.Children>): number {
    return header.has('totalUsers') ? header.getPriority('totalUsers') : 90;
  }

  toolbarItems(): ItemList<Mithril.Children> {
    const header = (this.header = this.attrs.headerItems());

    try {
      const items = super.toolbarItems();
      const total = header.has('totalUsers') ? header.get('totalUsers') : null;
      const split = this.headerSplit(header);
      const rest = new ItemList<Mithril.Children>();

      Object.entries(header.toObject()).forEach(([key, item]) => {
        if (key !== 'search' && key !== 'totalUsers' && item.priority <= split) rest.add(key, item.content as Mithril.Children, item.priority);
      });

      items.setContent(
        'actions',
        <div className="ResourceTable-toolbarActions UserListPage-header">
          {total !== null && this.countView(total)}
          {rest.toArray()}
        </div>
      );

      return items;
    } finally {
      this.header = null;
    }
  }

  viewItems(): ItemList<Mithril.Children> {
    const items = super.viewItems();
    const header = this.header ?? this.attrs.headerItems();
    const split = this.headerSplit(header);

    if (items.has('search')) items.remove('search');
    if (items.has('sort')) items.remove('sort');

    Object.entries(header.toObject()).forEach(([key, item]) => {
      if (key === 'totalUsers' || (key !== 'search' && item.priority <= split)) return;

      if (key === 'search') {
        items.add('search', <div className="ResourceTable-searchGroup">{item.content as Mithril.Children}</div>, item.priority);
      } else {
        items.add(`header-${key}`, item.content as Mithril.Children, item.priority);
      }
    });

    return items;
  }

  bulkActionItems(): ItemList<Mithril.Children> {
    return this.attrs.bulkActions(this.bulkContext());
  }

  bulkContext(): BulkActionContext<User> {
    const context = super.bulkContext();
    const showResult = context.showResult;

    return {
      ...context,
      showResult: (summary: BulkSummary<User>) => {
        // `ok` holds every record the action succeeded for; only deleted ones leave the total.
        const deleted = summary.ok.filter((user) => !user.exists).length;

        if (deleted) this.attrs.onBulkDeleted(deleted);

        showResult(summary);
      },
    };
  }

  headerView(key: string, column: ColumnData): Mithril.Children {
    return super.headerView(key, { ...column, className: classList(column.className, 'UserListPage-grid-header') });
  }

  rowView(user: User, columns: [string, ColumnData][], selectable: boolean = false): Mithril.Children {
    const shaded = this.list.getItems().indexOf(user) % 2 > 0;

    return super.rowView(
      user,
      columns.map(([key, column]) => [
        key,
        {
          ...column,
          className: classList(column.className, 'UserListPage-grid-rowItem', shaded && 'UserListPage-grid-rowItem--shaded'),
          attrs: (model: User) => ({ ...(column.attrs?.(model) ?? {}), 'data-user-id': model.id() }),
        },
      ]),
      selectable
    );
  }
}

/**
 * Admin page which displays a paginated list of all users on the forum.
 */
export default class UserListPage extends AdminPage {
  /**
   * The users resource; its descriptor drives the sorts, the filter row and extension columns.
   */
  protected definition!: ResourceDefinition<User>;

  /**
   * The list: search, sort, filters and the current page (synced with the URL).
   */
  protected list!: ResourceListState<User>;

  /**
   * Search text typed but not yet sent.
   */
  protected searchDraft: string | null = null;

  /** `definition.fields()`, built once the descriptor is loaded. */
  private descriptorFields: ItemList<Field<User>> | null = null;

  private throttledSearch = debounce(250, () => {
    const query = this.searchDraft ?? '';

    this.list
      .setQuery(query)
      .catch(() => {
        // The request has already shown its error.
      })
      .finally(() => {
        if (this.searchDraft === query) this.searchDraft = null;
        m.redraw();
      });
  });

  /**
   * Total number of forum users.
   *
   * Fetched from the active `AdminApplication` (`app`), with
   * data provided by `AdminPayload.php`, or `flarum/statistics`
   * if installed.
   */
  private userCount: number = app.data.modelStatistics.users.total;

  /**
   * The current search query, including text not yet sent.
   */
  protected get query(): string {
    return this.searchDraft ?? this.list.getQuery();
  }

  /**
   * This page's array of users.
   *
   * `undefined` until a page has been fetched (and while the list reloads from scratch).
   */
  protected get pageData(): User[] | undefined {
    return this.list.getPages().length ? this.list.getItems() : undefined;
  }

  /**
   * Number of users to load per page.
   */
  protected get numPerPage(): number {
    return this.list.pageSize ?? 50;
  }

  oninit(vnode: Mithril.Vnode<IPageAttrs, this>) {
    super.oninit(vnode);

    this.definition = new ResourceDefinition<User>('users');
    // The table loads the list, reading the 1-based `?page=` (and sort, search, filters) from the URL.
    this.list = new ResourceListState<User>(this.definition, { pageSize: 50 });
  }

  onremove(vnode: Mithril.VnodeDOM<IPageAttrs, this>) {
    super.onremove(vnode);

    this.throttledSearch.cancel();
  }

  /**
   * Component to render.
   */
  content() {
    const loading = this.pageData === undefined;

    return [
      <UserListTable
        definition={this.definition}
        state={this.list}
        className="UserListPage-table"
        tableClassName={classList(
          'UserListPage-grid',
          loading ? 'UserListPage-grid--loading' : this.list.isLoading() ? 'UserListPage-grid--loadingPage' : 'UserListPage-grid--loaded'
        )}
        columns={this.tableColumns()}
        actionsColumn={false}
        exportable={this.canExport()}
        bulkActions={(context: BulkActionContext<User>) => this.bulkActionItems(context)}
        onBulkDeleted={(count: number) => this.setUserCount(this.userCount - count)}
        rowAttrs={(user: User) => ({ 'data-user-id': user.id() })}
        onCreate={() => this.showCreateModal()}
        onEdit={(user: User) => this.showEditModal(user)}
        headerItems={() => this.headerItems()}
      />,
    ];
  }

  headerItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    const onchange = (value: string) => {
      this.searchDraft = value;
      this.throttledSearch();
    };

    items.add(
      'search',
      <GambitsAutocompleteDropdown resource="users" query={this.query} onchange={onchange}>
        <Input
          type="search"
          placeholder={app.translator.trans('core.admin.users.search_placeholder')}
          ariaLabel={extractText(app.translator.trans('core.admin.users.search_placeholder'))}
          prefixIcon="fas fa-search"
          clearable={true}
          loading={this.searchDraft !== null || this.list.isLoading()}
          value={this.query}
          onchange={onchange}
        />
      </GambitsAutocompleteDropdown>,
      100
    );

    items.add(
      'totalUsers',
      <p class="UserListPage-totalUsers">{app.translator.trans('core.admin.users.total_users', { count: this.userCount })}</p>,
      90
    );

    items.add('actions', <div className="UserListPage-actions">{this.actionItems().toArray()}</div>, 80);

    return items;
  }

  actionItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'createUser',
      <Button className="Button UserListPage-createUserBtn" icon="fas fa-user-plus" onclick={() => this.showCreateModal()}>
        {app.translator.trans('core.admin.users.create_user_button')}
      </Button>,
      100
    );

    return items;
  }

  /**
   * The modal the "New User" button opens; it gets an `onCreated(user)` callback.
   *
   * @experimental 2.1 may return a `ResourceFormModal` subclass.
   */
  createModal(): UserListModal {
    return CreateUserModal;
  }

  /**
   * The modal a row's "Edit" opens; it gets `user` and an `onsaved(user)` callback.
   *
   * @experimental 2.1 may return a `ResourceFormModal` subclass.
   */
  editModal(_user: User): UserListModal {
    return loadEditUserModal;
  }

  /**
   * Actions for the selected users. Empty, so the list has no checkbox column; adding an item adds
   * it. `context.list` holds the selection; `ResourceDefinition.bulkDelete(context)` is a ready-made
   * item.
   *
   * @experimental
   */
  bulkActionItems(_context: BulkActionContext<User>): ItemList<Mithril.Children> {
    return new ItemList<Mithril.Children>();
  }

  /**
   * Whether the "Export CSV" button shows: administrators only, since the export holds email
   * addresses.
   *
   * @experimental
   */
  canExport(): boolean {
    return !!app.session.user?.isAdmin();
  }

  /**
   * Build an item list of columns to show for each user.
   *
   * Each column in the list should be an object with keys `name` and `content`.
   *
   * `name` is a string that will be used as the column name.
   * `content` is a function with the User model passed as the first and only argument.
   *
   * Optional keys: `sort` (a users sort, which makes the header sort the list), `className`,
   * `attrs`, `toggleable` (false keeps it out of the "Columns" dropdown), `hidden` (hidden until
   * shown) and `csv` (the column's CSV value; without it the column is left out of the export unless
   * its key is a users attribute).
   *
   * See `UserListPage.tsx` for examples.
   */
  columns(): ItemList<ColumnData> {
    const columns = new ItemList<ColumnData>();

    columns.add(
      'id',
      {
        name: app.translator.trans('core.admin.users.grid.columns.user_id.title'),
        content: (user: User) => user.id() ?? null,
        toggleable: false,
      },
      100
    );

    columns.add(
      'username',
      {
        name: app.translator.trans('core.admin.users.grid.columns.username.title'),
        content: (user: User) => {
          const profileUrl = `${app.forum.attribute('baseUrl')}/u/${user.slug()}`;

          return (
            <a
              target="_blank"
              href={profileUrl}
              title={extractText(app.translator.trans('core.admin.users.grid.columns.username.profile_link_tooltip', { username: user.username() }))}
            >
              {user.username()}
            </a>
          );
        },
        sort: 'username',
        toggleable: false,
      },
      90
    );

    columns.add(
      'displayName',
      {
        name: app.translator.trans('core.admin.users.grid.columns.display_name.title'),
        content: (user: User) => user.displayName(),
        // Nickname display names carry zero-width spaces (the nickname driver's email hardening).
        csv: (user: User) => (user.displayName() ?? '').replace(/\u200B/g, ''),
      },
      85
    );

    columns.add(
      'joinDate',
      {
        name: app.translator.trans('core.admin.users.grid.columns.join_time.title'),
        content: (user: User) => (
          <span className="UserList-joinDate" title={user.joinTime()}>
            {app.translator.formatDateTime(dayjs(user.joinTime()), 'core.lib.datetime_formats.userListJoinDate')}
          </span>
        ),
        sort: 'joinedAt',
        csv: (user: User) => user.joinTime()?.toISOString() ?? null,
      },
      80
    );

    columns.add(
      'groupBadges',
      {
        name: app.translator.trans('core.admin.users.grid.columns.group_badges.title'),
        content: (user: User) => {
          const badges = user.badges().toArray();

          if (badges.length) {
            return <ul className="DiscussionHero-badges badges">{listItems(badges)}</ul>;
          } else {
            return app.translator.trans('core.admin.users.grid.columns.group_badges.no_badges');
          }
        },
        csv: (user: User) =>
          (user.groups() || [])
            .filter(Boolean)
            .map((group) => group!.nameSingular())
            .join('; '),
      },
      70
    );

    columns.add(
      'emailAddress',
      {
        name: app.translator.trans('core.admin.users.grid.columns.email.title'),
        content: (user: User) => {
          function setEmailVisibility(visible: boolean) {
            // Get needed jQuery element refs
            const emailContainer = $(`[data-column-name=emailAddress][data-user-id=${user.id()}] .UserList-email`);
            const emailAddress = emailContainer.find('.UserList-emailAddress');
            const emailToggleButton = emailContainer.find('.UserList-emailIconBtn');
            const emailToggleButtonIcon = emailToggleButton.find('.icon');

            emailToggleButton.attr(
              'title',
              extractText(
                visible
                  ? app.translator.trans('core.admin.users.grid.columns.email.visibility_hide')
                  : app.translator.trans('core.admin.users.grid.columns.email.visibility_show')
              )
            );

            emailAddress.attr('aria-hidden', visible ? null : 'true');

            if (visible) {
              emailToggleButtonIcon.addClass('fa-eye');
              emailToggleButtonIcon.removeClass('fa-eye-slash');
            } else {
              emailToggleButtonIcon.removeClass('fa-eye');
              emailToggleButtonIcon.addClass('fa-eye-slash');
            }

            // Need the string interpolation to prevent TS error.
            emailContainer.attr('data-email-shown', `${visible}`);
          }

          function toggleEmailVisibility() {
            const emailContainer = $(`[data-column-name=emailAddress][data-user-id=${user.id()}] .UserList-email`);
            const emailShown = emailContainer.attr('data-email-shown') === 'true';

            if (emailShown) {
              setEmailVisibility(false);
            } else {
              setEmailVisibility(true);
            }
          }

          return (
            <div className="UserList-email" key={user.id()} data-email-shown="false">
              <span className="UserList-emailAddress" aria-hidden="true" onclick={() => setEmailVisibility(true)}>
                {user.email()}
              </span>
              <button
                onclick={toggleEmailVisibility}
                className="Button Button--text UserList-emailIconBtn"
                type="button"
                title={app.translator.trans('core.admin.users.grid.columns.email.visibility_show')}
              >
                <Icon name="far fa-eye-slash fa-fw" className="icon" />
              </button>
            </div>
          );
        },
        csv: (user: User) => user.email() ?? '',
      },
      70
    );

    columns.add(
      'userActions',
      {
        name: app.translator.trans('core.admin.users.grid.columns.user_actions.title'),
        content: (user: User) => (
          <Dropdown
            className="UserList-userActions"
            buttonClassName="Button UserList-userActionsBtn Button--icon Button--flat"
            menuClassName="Dropdown-menu--right"
            icon="fas fa-ellipsis-h"
          >
            {this.userActionItems(user).toArray()}
          </Dropdown>
        ),
        toggleable: false,
      },
      -90
    );

    return columns;
  }

  userActionItems(user: User): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'editUser',
      <Button
        icon="fas fa-pencil-alt"
        title={app.translator.trans('core.admin.users.grid.columns.user_actions.edit_user.tooltip', { username: user.displayName() }, true)}
        onclick={() => this.showEditModal(user)}
      >
        {app.translator.trans('core.admin.users.grid.columns.user_actions.edit_user.button')}
      </Button>
    );

    if (user.canDelete()) {
      items.add(
        'delete',
        <Button
          icon="fas fa-trash-alt"
          className="Button UserList-deleteUser"
          onclick={() =>
            app.modal.show(ResourceDeleteModal, {
              definition: this.definition,
              model: user,
              ondeleted: () => this.userDeleted(),
            })
          }
        >
          {app.translator.trans('core.admin.resource.table.delete_button')}
        </Button>,
        -100
      );
    }

    return items;
  }

  headerInfo() {
    return {
      className: 'UserListPage',
      icon: 'fas fa-users',
      title: app.translator.trans('core.admin.users.title'),
      description: app.translator.trans('core.admin.users.description'),
    };
  }

  /**
   * Load and display a page of users, with the current search, filters and sort.
   *
   * Uses `numPerPage` as the response limit, and calculates the offset required from `pageNumber`.
   *
   * @param pageNumber The **zero-based** page number to load and display
   */
  async loadPage(pageNumber: number) {
    if (pageNumber < 0) pageNumber = 0;

    try {
      await this.list.goto(pageNumber + 1);
    } catch {
      // The request has already shown its error.
    } finally {
      m.redraw();
    }
  }

  /**
   * @param page The **1-based** page number
   */
  goToPage(page: number) {
    this.loadPage(page - 1);
  }

  private showCreateModal(): void {
    app.modal.show(this.createModal(), {
      onCreated: () => {
        this.setUserCount(this.userCount + 1);
        this.loadPage(this.list.currentPage() - 1);
      },
    });
  }

  private showEditModal(user: User): void {
    app.modal.show(this.editModal(user), {
      user,
      onsaved: () => m.redraw(),
    });
  }

  private userDeleted(): void {
    const page = this.list.currentPage();

    this.setUserCount(this.userCount - 1);
    // Deleting the last row of a page goes back one page.
    this.loadPage((this.list.getItems().length <= 1 && page > 1 ? page - 1 : page) - 1);
  }

  private setUserCount(count: number): void {
    this.userCount = count;

    if (app.data?.modelStatistics?.users) {
      app.data.modelStatistics.users.total = count;
    }
  }

  /**
   * `columns()`, plus a column for each descriptor field hinted with `ui.column` (e.g. an extension's)
   * that the page doesn't already show. Fields that only have a column because they are sortable
   * get none.
   */
  private tableColumns(): ItemList<ColumnData> {
    const columns = new ItemList<ColumnData>();
    Object.entries(this.columns().toObject()).forEach(([key, item]) => {
      const column = item.content as ColumnData;

      // A fresh fallback per cell: one vnode must not be mounted in two places.
      columns.add(
        key,
        { ...column, content: (user: User) => column.content(user) || app.translator.trans('core.admin.users.grid.invalid_column_content') },
        item.priority
      );
    });

    if (!this.definition.descriptor) return columns;

    this.descriptorFields ??= this.definition.fields();

    Object.entries(this.descriptorFields.toObject()).forEach(([key, item]) => {
      const field = item.content as Field<User>;

      if (!field.column || !field.descriptor?.ui?.column || Object.prototype.hasOwnProperty.call(FIELD_COLUMNS, key) || columns.has(key)) return;

      columns.add(
        key,
        { name: field.label, content: (user: User) => renderColumn(key, field, user), sort: field.column.sort },
        field.column.priority ?? item.priority
      );
    });

    return columns;
  }
}
