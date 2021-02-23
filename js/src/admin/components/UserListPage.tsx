import EditUserModal from '../../common/components/EditUserModal';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Button from '../../common/components/Button';

import icon from '../../common/helpers/icon';
import listItems from '../../common/helpers/listItems';

import type User from '../../common/models/User';

import ItemList from '../../common/utils/ItemList';
import classList from '../../common/utils/classList';
import extractText from '../../common/utils/extractText';

import AdminPage from './AdminPage';

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
  data: Record<string, unknown>[];
  included: Record<string, unknown>[];
  links: {
    first: string;
    next?: string;
  };
  meta: {
    total: number;
  };
};

type UsersApiResponse = User[] & { payload: ApiPayload };

/**
 * Admin page which displays a paginated list of all users on the forum.
 */
export default class UserListPage extends AdminPage {
  /**
   * Number of users to load per page.
   */
  private numPerPage: number = 3;

  /**
   * Current page number. Zero-indexed.
   */
  private pageNumber: number = 0;

  /**
   * Total number of forum users.
   */
  userCount: number = -1;

  /**
   * Get total number of user pages.
   */
  private getTotalPageCount(): number {
    if (this.userCount === -1) return 0;

    return Math.ceil(this.userCount / this.numPerPage);
  }

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

  private isLoadingPage: boolean = false;

  headerInfo() {
    return {
      className: 'UserListPage',
      icon: 'fas fa-users',
      title: app.translator.trans('core.admin.user_list.title'),
      description: app.translator.trans('core.admin.user_list.description'),
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
  async loadNextUserSet(offset: number = 0, pageNumber) {
    // You shouldn't ever be negative
    if (offset < 0) offset = 0;

    app.store
      .find('users', {
        page: {
          limit: this.numPerPage,
          offset,
        },
      })
      .then((apiData) => {
        // Next link won't be present if there's no more data
        this.moreData = !!apiData.payload.links.next;
        this.userCount = (apiData.payload.meta && apiData.payload.meta.totalCount) || -1;

        let data = apiData;

        delete data.payload;

        this.pageData = data;
        this.pageNumber = pageNumber;
        this.isLoadingPage = false;

        m.redraw();
      })
      .catch((err) => {
        console.error(err);
        this.pageData = [];
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
   * See `UserListPage.tsx` for examples.
   */
  columns(): ItemList {
    const columns = new ItemList();

    columns.add(
      'id',
      {
        name: app.translator.trans('core.admin.user_list.grid.default_columns.id'),
        content: (user: User) => user.id(),
      },
      100
    );

    columns.add(
      'username',
      {
        name: app.translator.trans('core.admin.user_list.grid.default_columns.username'),
        content: (user: User) => user.username(),
      },
      90
    );

    columns.add(
      'joinDate',
      {
        name: app.translator.trans('core.admin.user_list.grid.default_columns.join_time'),
        content: (user: User) => (
          <span class="UserList-joinDate" title={user.joinTime()}>
            {dayjs(user.joinTime()).format('LLL')}
          </span>
        ),
      },
      80
    );

    columns.add(
      'groupBadges',
      {
        name: app.translator.trans('core.admin.user_list.grid.default_columns.group_badges'),
        content: (user: User) => {
          const badges = user.badges().toArray();

          if (badges.length) {
            return <ul className="DiscussionHero-badges badges">{listItems(badges)}</ul>;
          } else {
            return app.translator.trans('core.admin.user_list.grid.default_columns.group_badges_none');
          }
        },
      },
      70
    );

    columns.add(
      'emailAddress',
      {
        name: app.translator.trans('core.admin.user_list.grid.default_columns.email'),
        content: (user: User) => {
          return (
            <div class="UserList-email" data-user-id={user.id()} data-email-shown="false">
              <span class="UserList-emailAddress">{app.translator.trans('core.admin.user_list.grid.default_columns.email_hidden')}</span>
              <button
                onclick={() => {
                  // Get needed jQuery element refs
                  const emailContainer = $(`.UserList-email[data-user-id=${user.id()}]`);
                  const emailAddress = emailContainer.find('.UserList-emailAddress');
                  const emailToggleButton = emailContainer.find('.UserList-emailIconBtn');
                  const emailToggleButtonIcon = emailToggleButton.find('.icon');

                  const emailShown = emailContainer.attr('data-email-shown') === 'true';

                  if (emailShown) {
                    //! Email currently shown, switching to hidden

                    // Update tooltip
                    emailToggleButton.attr(
                      'title',
                      extractText(app.translator.trans('core.admin.user_list.grid.default_columns.email_visibility_hide'))
                    );

                    // Replace real email with placeholder email
                    emailAddress.text(app.translator.trans('core.admin.user_list.grid.default_columns.email_hidden'));

                    // Change button icons
                    emailToggleButtonIcon.addClass('fa-eye');
                    emailToggleButtonIcon.removeClass('fa-eye-slash');
                  } else {
                    //! Email currently hidden, switching to shown

                    // Update tooltip
                    emailToggleButton.attr(
                      'title',
                      extractText(app.translator.trans('core.admin.user_list.grid.default_columns.email_visibility_show'))
                    );

                    // Replace placeholder email with real email
                    emailAddress.text(user.email());

                    // Change button icons
                    emailToggleButtonIcon.removeClass('fa-eye');
                    emailToggleButtonIcon.addClass('fa-eye-slash');
                  }

                  emailContainer.attr('data-email-shown', !emailShown);
                }}
                class="UserList-emailIconBtn"
                title={app.translator.trans('core.admin.user_list.grid.default_columns.email_show')}
              >
                {icon('far fa-eye', { className: 'icon' })}
              </button>
            </div>
          );
        },
      },
      70
    );

    columns.add(
      'editUser',
      {
        name: app.translator.trans('core.admin.user_list.grid.default_columns.edit_user'),
        content: (user: User) => (
          <Button
            className="Button UserList-editModalBtn"
            title={app.translator.trans('core.admin.user_list.grid.default_columns.edit_user_tooltip', { username: user.username() })}
            onclick={() => app.modal.show(EditUserModal, { user })}
          >
            {app.translator.trans('core.admin.user_list.grid.default_columns.edit_user_button')}
          </Button>
        ),
      },
      -90
    );

    columns.add(
      'profileLink',
      {
        name: app.translator.trans('core.admin.user_list.grid.default_columns.profile_link'),
        content: (user: User) => {
          const profileUrl = `${app.forum.attribute('baseUrl')}/u/${user.slug()}`;

          return (
            <a
              class="UserList-profileLink"
              target="_blank"
              href={profileUrl}
              title={extractText(
                app.translator.trans('core.admin.user_list.grid.default_columns.profile_link_tooltip', { username: user.username() })
              )}
            >
              {icon('fas fa-link')}
            </a>
          );
        },
      },
      // This should probably come last unless an ext really wants to be last
      -100
    );

    return columns;
  }

  nextPage() {
    this.isLoadingPage = true;
    // Make sure the render doesn't end up happening after the API refresh
    m.redraw.sync();

    const newPageNum = this.pageNumber + 1;
    this.loadNextUserSet(newPageNum * this.numPerPage, newPageNum);
  }

  previousPage() {
    this.isLoadingPage = true;
    // Make sure the render doesn't end up happening after the API refresh
    m.redraw.sync();

    const newPageNum = this.pageNumber - 1;
    this.loadNextUserSet(newPageNum * this.numPerPage, newPageNum);
  }

  /**
   * Component to render.
   */
  content() {
    if (typeof this.pageData === 'undefined') {
      this.loadNextUserSet(0, 0);

      return [
        <section class="UserListPage-grid UserListPage-grid--loading">
          <LoadingIndicator />
        </section>,
      ];
    }

    const columns: (ColumnData & { itemName: string })[] = this.columns().toArray();

    return [
      <p class="UserListPage-totalUsers">{app.translator.trans('core.admin.user_list.total_users', { count: this.userCount })}</p>,
      <section
        class={classList(['UserListPage-grid', this.isLoadingPage ? 'UserListPage-grid--loadingPage' : 'UserListPage-grid--loaded'])}
        style={`grid-template-columns: repeat(${columns.length}, minmax(max-content, 300px))`}
      >
        {/* Render columns */}
        {columns.map((column) => (
          <div class="UserListPage-grid--header">{column.name}</div>
        ))}

        {/* Render user data */}
        {this.pageData.map((user, rowIndex) =>
          columns.map((col) => {
            const columnContent = col.content && col.content(user);

            return (
              <div
                class={classList(['UserListPage-grid--rowItem', rowIndex % 2 > 0 && 'UserListPage-grid--shadedRow'])}
                data-user-id={user.id()}
                data-column-name={col.itemName}
              >
                {columnContent || 'Invalid'}
              </div>
            );
          })
        )}

        {/* Loading spinner that shows when a new page is being loaded */}
        {this.isLoadingPage && <LoadingIndicator />}
      </section>,
      <nav class="UserListPage-gridPagination">
        <Button
          disabled={this.pageNumber === 0}
          title={app.translator.trans('core.admin.user_list.pagination.back_button')}
          onclick={this.previousPage.bind(this)}
          icon="fas fa-chevron-left"
          className="Button UserListPage-backBtn"
        />
        <span class="UserListPage-pageNumber">
          {app.translator.trans('core.admin.user_list.pagination.page_counter', {
            current: this.pageNumber + 1,
            total: this.getTotalPageCount(),
          })}
        </span>
        <Button
          disabled={!this.moreData}
          title={app.translator.trans('core.admin.user_list.pagination.next_button')}
          onclick={this.nextPage.bind(this)}
          icon="fas fa-chevron-right"
          className="Button UserListPage-nextBtn"
        />
      </nav>,
    ];
  }
}
