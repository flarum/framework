import GroupBadge from '../../common/components/GroupBadge';
import EditGroupModal from './EditGroupModal';
import Group from '../../common/models/Group';
import icon from '../../common/helpers/icon';
import type User from '../../common/models/User';
import AdminPage from './AdminPage';
import ItemList from '../../common/utils/ItemList';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import classList from '../../common/utils/classList';
import Button from '../../common/components/Button';

type ColumnData = {
  /**
   * Column title
   */
  name: String;
  /**
   * Component(s) to show for this column.
   */
  content: (user: User) => JSX.Element;
};

type ApiPayload = {
  data: [];
  included: [];
  links: {
    first: string;
    next?: string;
  };
};

type UsersApiResponse = User[] & { payload: ApiPayload };

/**
 * Admin page which displays a paginated list of all users on the forum.
 */
export default class UsersListPage extends AdminPage {
  /**
   * Number of users to load per page.
   */
  private numPerPage: number = 25;

  /**
   * Current page number. Zero-indexed.
   */
  private pageNumber: number = 0;

  /**
   * This page's array of users.
   *
   * `undefined` when page loads as no data has been fetched.
   */
  private pageData: User[] | undefined = undefined;

  /**
   * Are there more users available?
   */
  private moreData: boolean = false;

  headerInfo() {
    return {
      className: 'UsersListPage',
      icon: 'fas fa-users',
      title: app.translator.trans('core.admin.userslist.title'),
      description: app.translator.trans('core.admin.userslist.description'),
    };
  }

  /**
   * Asynchronously fetch the next set of users to be rendered.
   *
   * Returns an array of Users, plus the raw API payload.
   *
   * Uses the `this.numPerPage` as the response limit.
   *
   * @param offset Offset to pass to API
   */
  async loadNextUserSet(offset: number = 0): Promise<UsersApiResponse> {
    // You shouldn't ever be negative
    if (offset < 0) offset = 0;

    return app.store.find('users', {
      // filter: { q: 'f' },
      page: { limit: this.numPerPage, offset },
    });
  }

  /**
   * Build an item list of columns to show for each user.
   *
   * Each column in the list should be an object with keys `name` and `content`.
   *
   * `name` is a string that will be used as the column name.
   * `content` is a function with the User model passed as the first and only argument.
   *
   * See `UsersListPage.tsx` for examples.
   */
  columns(): ItemList {
    const columns = new ItemList();

    columns.add('id', {
      name: app.translator.trans('core.admin.userslist.grid.default_columns.id'),
      content: (user: User) => user.id(),
    });

    columns.add('username', {
      name: app.translator.trans('core.admin.userslist.grid.default_columns.username'),
      content: (user: User) => user.username(),
    });

    columns.add('joinDate', {
      name: app.translator.trans('core.admin.userslist.grid.default_columns.join_time'),
      // content: (user: User) => <span>{dayjs(user.joinTime(), 'LLLL')}</span>,
      content: (user: User) => (
        <span class="UsersList-joinDate" title={user.joinTime()}>
          {dayjs(user.joinTime()).format('LLL')}
        </span>
      ),
    });

    return columns;
  }

  /**
   * Component to render.
   */
  content() {
    if (typeof this.pageData === 'undefined') {
      this.loadNextUserSet()
        .then((apiData) => {
          console.log(apiData.payload);

          // Next link won't be present if there's no more data
          this.moreData = !!apiData.payload.links.next;

          let data = apiData;
          delete data.payload;

          this.pageData = data;
          m.redraw();
        })
        .catch(() => {
          this.pageData = [];
        });

      return [
        <section class="UsersListPage-grid UsersListPage-grid--loading">
          <LoadingIndicator />
        </section>,
      ];
    }

    const columns: ColumnData[] = this.columns().toArray();

    console.log('moar', this.moreData);

    return [
      <section class="UsersListPage-grid UsersListPage-grid--loaded" style={`grid-template-columns: repeat(${columns.length}, minmax(10px, auto))`}>
        {/* Render columns */}
        {columns.map((column) => (
          <div class="UsersListPage-grid--header">{column.name}</div>
        ))}

        {/* Render user data */}
        {this.pageData.map((user, rowIndex) =>
          columns.map((col) => {
            const columnContent = col.content && col.content(user);

            return (
              <div
                class={classList(['UsersListPage-grid--rowItem', rowIndex % 2 === 0 && 'UsersListPage-grid--shadedRow'])}
                data-user-id={user.id()}
                data-column-name={col.name}
              >
                {columnContent || 'Invalid'}
              </div>
            );
          })
        )}
      </section>,
      this.moreData ? (
        <nav>
          <Button.component />
          <span>Page {this.pageNumber}</span>
          <Button.component />
        </nav>
      ) : (
        <nav>
          <Button.component />
          <span>
            Page {this.pageNumber}/{this.pageNumber}
          </span>
        </nav>
      ),
    ];
  }
}
