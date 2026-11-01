import Mithril from 'mithril';

import app from '../../admin/app';

import Button from '../../common/components/Button';

import listItems from '../../common/helpers/listItems';

import type User from '../../common/models/User';
import type { IPageAttrs } from '../../common/components/Page';
import type ModalManagerState from '../../common/states/ModalManagerState';

import ItemList from '../../common/utils/ItemList';
import classList from '../../common/utils/classList';
import extractText from '../../common/utils/extractText';
import AdminPage from './AdminPage';
import CreateUserModal from './CreateUserModal';
import Icon from '../../common/components/Icon';
import { renderColumn } from '../../common/resource/field';
import RelationInput from '../../common/resource/inputs/RelationInput';
import ResourceDefinition from '../../common/resource/ResourceDefinition';
import ResourceListState from '../../common/resource/ResourceListState';
import type { Field } from '../../common/resource/types';
import ResourceTable, { type ColumnData as ResourceColumnData, type ResourceTableAttrs } from './resource/ResourceTable';

/**
 * One user list column. `{name, content}` is all an extension needs; the other keys (`sort`,
 * `className`, `attrs`, `toggleable`, `hidden`, `csv`) are `ResourceTable`'s.
 */
export type ColumnData = ResourceColumnData<User>;

/**
 * What `createModal()` / `editModal()` return: anything `app.modal.show()` takes, a modal class or a
 * loader of one.
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

const loadEditUserModal = () => import('../../common/components/EditUserModal');

interface UserListTableAttrs extends ResourceTableAttrs<User> {
  /** Opens the page's create modal. */
  createUser: () => void;
  /** Opens the page's edit modal. */
  editUser: (user: User) => void;
  /** The page's `actionItems()`, which replace the table's create button. */
  actionItems: () => ItemList<Mithril.Children>;
  /** Called with the table once it is created, so the page's row menus can be built from it. */
  ontable?: (table: UserListTable) => void;
}

/**
 * The user list's table. The page's `actionItems()` ("New User") replace the table's create button
 * at the right of the toolbar, and its `columns()` replace the table's columns, the `userActions`
 * column (`userActionItems()`) included. The table container keeps the `UserListPage-grid`
 * classes, the rows and cells `data-user-id`, the cells the `UserListPage-grid-*` classes. Create
 * and edit open the page's modals.
 */
class UserListTable extends ResourceTable<User, UserListTableAttrs> {
  oninit(vnode: Mithril.Vnode<UserListTableAttrs, this>) {
    super.oninit(vnode);

    this.attrs.ontable?.(this);
  }

  actionItems(): ItemList<Mithril.Children> {
    const items = super.actionItems();

    if (items.has('create')) items.remove('create');

    return items.merge(this.attrs.actionItems());
  }

  create(): void {
    this.attrs.createUser();
  }

  edit(user: User): void {
    this.attrs.editUser(user);
  }

  containerClassName(): string {
    const state = !this.list.getPages().length ? 'loading' : this.list.isLoading() ? 'loadingPage' : 'loaded';

    return classList(super.containerClassName(), 'UserListPage-grid', `UserListPage-grid--${state}`);
  }

  headerView(key: string, column: ColumnData): Mithril.Children {
    return super.headerView(key, { ...column, className: classList(column.className, 'UserListPage-grid-header') });
  }

  rowView(user: User, columns: [string, ColumnData][], selectable: boolean = false): Mithril.Children {
    return super.rowView(
      user,
      columns.map(([key, column]) => [
        key,
        {
          ...column,
          className: classList(column.className, 'UserListPage-grid-rowItem'),
          attrs: (model: User) => ({ ...(column.attrs?.(model) ?? {}), 'data-user-id': model.id() }),
        },
      ]),
      selectable
    );
  }

  rowAttrs(user: User): Record<string, unknown> {
    return { ...super.rowAttrs(user), 'data-user-id': user.id() };
  }
}

/**
 * Admin page which displays a paginated list of all users on the forum, in the shared
 * `ResourceTable` layout.
 */
export default class UserListPage extends AdminPage {
  /**
   * The users resource; its descriptor drives the sorts, the filters and extension columns.
   */
  protected definition!: ResourceDefinition<User>;

  /**
   * The list: search, sort, filters and the current page (synced with the URL).
   */
  protected list!: ResourceListState<User>;

  /** The table, once created; the row menus are built from its row actions. */
  protected table: UserListTable | null = null;

  /** `definition.fields()`, built once the descriptor is loaded. */
  private descriptorFields: ItemList<Field<User>> | null = null;

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

    this.definition = app.resources.get<User>('users');
    // The table loads the list, reading the 1-based `?page=` (and sort, search, filters) from the URL.
    this.list = new ResourceListState<User>(this.definition, { pageSize: 50 });
  }

  /**
   * Component to render.
   */
  content() {
    return [
      <UserListTable
        definition={this.definition}
        state={this.list}
        className="UserListPage-table"
        columns={this.tableColumns()}
        createUser={() => this.showCreateModal()}
        editUser={(user: User) => this.showEditModal(user)}
        actionItems={() => this.actionItems()}
        ontable={(table: UserListTable) => (this.table = table)}
      />,
    ];
  }

  /** The page's toolbar items, at the right of the table's toolbar: `createUser` ("New User"). */
  actionItems(): ItemList<Mithril.Children> {
    const items = new ItemList<Mithril.Children>();

    items.add(
      'createUser',
      <Button className="Button Button--primary UserListPage-createUserBtn" icon="fas fa-user-plus" onclick={() => this.showCreateModal()}>
        {app.translator.trans('core.admin.users.create_user_button')}
      </Button>,
      -100
    );

    return items;
  }

  /**
   * The modal the "New User" button opens; it gets an `onCreated(user)` callback.
   */
  createModal(): UserListModal {
    return CreateUserModal;
  }

  /**
   * The modal a row's "Edit" opens; it gets `user` and an `onsaved(user)` callback.
   */
  editModal(_user: User): UserListModal {
    return loadEditUserModal;
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
        name: <span className="visually-hidden">{app.translator.trans('core.admin.users.grid.columns.user_actions.title')}</span>,
        content: (user: User) => this.table?.rowActionsMenu(user, this.userActionItems(user)) ?? null,
        className: 'ResourceTable-actions',
        toggleable: false,
      },
      -90
    );

    return columns;
  }

  /**
   * A user's overflow menu: the table's `rowActionItems()` (`edit`, the other row actions, then
   * `delete` after a separator). Add an item at an ordinary priority to place it among the
   * non-destructive ones.
   */
  userActionItems(user: User): ItemList<Mithril.Children> {
    return this.table?.rowActionItems(user) ?? new ItemList<Mithril.Children>();
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
      onCreated: () => this.loadPage(this.list.currentPage() - 1),
    });
  }

  private showEditModal(user: User): void {
    app.modal.show(this.editModal(user), {
      user,
      onsaved: () => m.redraw(),
    });
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
