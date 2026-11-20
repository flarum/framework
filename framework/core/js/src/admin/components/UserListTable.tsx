import type Mithril from 'mithril';

import type User from '../../common/models/User';
import type ItemList from '../../common/utils/ItemList';
import classList from '../../common/utils/classList';
import ResourceTable, { type ColumnData as ResourceColumnData, type ResourceTableAttrs } from './resource/ResourceTable';

type ColumnData = ResourceColumnData<User>;

export interface UserListTableAttrs extends ResourceTableAttrs<User> {
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
export default class UserListTable extends ResourceTable<User, UserListTableAttrs> {
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
