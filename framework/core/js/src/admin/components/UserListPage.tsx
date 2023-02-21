import type Mithril from 'mithril';

import app from '../../admin/app';

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
import { debounce } from '../../common/utils/throttleDebounce';

type ColumnData = {
  /**
   * Column title
   */
  name: Mithril.Children;
  /**
   * Component(s) to show for this column.
   */
  content: (user: User) => Mithril.Children;
};

/**
 * Admin page which displays a paginated list of all users on the forum.
 */
export default class UserListPage extends AdminPage {
  private query: string = '';
  private throttledSearch = debounce(250, () => this.loadPage(0));

  /**
   * Number of users to load per page.
   */
  private numPerPage: number = 50;

  /**
   * Current page number. Zero-indexed.
   */
  private pageNumber: number = 0;

  /**
   * Page number being loaded. Zero-indexed.
   */
  private loadingPageNumber: number = 0;

  /**
   * Total number of forum users.
   *
   * Fetched from the active `AdminApplication` (`app`), with
   * data provided by `AdminPayload.php`, or `flarum/statistics`
   * if installed.
   */
  readonly userCount: number = app.data.modelStatistics.users.total;

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

  /**
   * Component to render.
   */
  content() {
    if (typeof this.pageData === 'undefined') {
      this.loadPage(0);

      return [
        <section class="UserListPage-grid UserListPage-grid--loading">
          <LoadingIndicator containerClassName="LoadingIndicator--block" size="large" />
        </section>,
      ];
    }

    const columns = this.columns().toArray();

    return [
      <div className="Search-input">
        <input
          className="FormControl SearchBar"
          type="search"
          placeholder={app.translator.trans('core.admin.users.search_placeholder')}
          oninput={(e: InputEvent) => {
            this.isLoadingPage = true;
            this.query = (e?.target as HTMLInputElement)?.value;
            this.throttledSearch();
          }}
        />
      </div>,
      <p class="UserListPage-totalUsers">{app.translator.trans('core.admin.users.total_users', { count: this.userCount })}</p>,
      <section
        class={classList(['UserListPage-grid', this.isLoadingPage ? 'UserListPage-grid--loadingPage' : 'UserListPage-grid--loaded'])}
        style={{ '--columns': columns.length }}
        role="table"
        // +1 to account for header
        aria-rowcount={this.pageData.length + 1}
        aria-colcount={columns.length}
        aria-live="polite"
        aria-busy={this.isLoadingPage ? 'true' : 'false'}
      >
        {/* Render columns */}
        {columns.map((column, colIndex) => (
          <div class="UserListPage-grid-header" role="columnheader" aria-colindex={colIndex + 1} aria-rowindex={1}>
            {column.name}
          </div>
        ))}

        {/* Render user data */}
        {this.pageData.map((user, rowIndex) =>
          columns.map((col, colIndex) => {
            const columnContent = col.content && col.content(user);

            return (
              <div
                class={classList(['UserListPage-grid-rowItem', rowIndex % 2 > 0 && 'UserListPage-grid-rowItem--shaded'])}
                data-user-id={user.id()}
                data-column-name={col.itemName}
                aria-colindex={colIndex + 1}
                // +2 to account for 0-based index, and for the header row
                aria-rowindex={rowIndex + 2}
                role="cell"
              >
                {columnContent || app.translator.trans('core.admin.users.grid.invalid_column_content')}
              </div>
            );
          })
        )}

        {/* Loading spinner that shows when a new page is being loaded */}
        {this.isLoadingPage && <LoadingIndicator size="large" />}
      </section>,
      <nav class="UserListPage-gridPagination">
        <Button
          disabled={this.pageNumber === 0}
          title={app.translator.trans('core.admin.users.pagination.first_page_button')}
          onclick={this.goToPage.bind(this, 1)}
          icon="fas fa-step-backward"
          className="Button Button--icon UserListPage-firstPageBtn"
        />
        <Button
          disabled={this.pageNumber === 0}
          title={app.translator.trans('core.admin.users.pagination.back_button')}
          onclick={this.previousPage.bind(this)}
          icon="fas fa-chevron-left"
          className="Button Button--icon UserListPage-backBtn"
        />
        <span class="UserListPage-pageNumber">
          {app.translator.trans('core.admin.users.pagination.page_counter', {
            current: (
              <input
                type="text"
                value={this.loadingPageNumber + 1}
                aria-label={extractText(app.translator.trans('core.admin.users.pagination.go_to_page_textbox_a11y_label'))}
                autocomplete="off"
                className="FormControl UserListPage-pageNumberInput"
                onchange={(e: InputEvent) => {
                  const target = e.target as HTMLInputElement;
                  let pageNumber = parseInt(target.value);

                  if (isNaN(pageNumber)) {
                    // Invalid value, reset to current page
                    target.value = (this.pageNumber + 1).toString();
                    return;
                  }

                  if (pageNumber < 1) {
                    // Lower constraint
                    pageNumber = 1;
                  } else if (pageNumber > this.getTotalPageCount()) {
                    // Upper constraint
                    pageNumber = this.getTotalPageCount();
                  }

                  target.value = pageNumber.toString();

                  this.goToPage(pageNumber);
                }}
              />
            ),
            currentNum: this.pageNumber + 1,
            total: this.getTotalPageCount(),
          })}
        </span>
        <Button
          disabled={!this.moreData}
          title={app.translator.trans('core.admin.users.pagination.next_button')}
          onclick={this.nextPage.bind(this)}
          icon="fas fa-chevron-right"
          className="Button Button--icon UserListPage-nextBtn"
        />
        <Button
          disabled={!this.moreData}
          title={app.translator.trans('core.admin.users.pagination.last_page_button')}
          onclick={this.goToPage.bind(this, this.getTotalPageCount())}
          icon="fas fa-step-forward"
          className="Button Button--icon UserListPage-lastPageBtn"
        />
      </nav>,
    ];
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
  columns(): ItemList<ColumnData> {
    const columns = new ItemList<ColumnData>();

    columns.add(
      'id',
      {
        name: app.translator.trans('core.admin.users.grid.columns.user_id.title'),
        content: (user: User) => user.id() ?? '',
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
      },
      90
    );

    columns.add(
      'joinDate',
      {
        name: app.translator.trans('core.admin.users.grid.columns.join_time.title'),
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
        name: app.translator.trans('core.admin.users.grid.columns.group_badges.title'),
        content: (user: User) => {
          const badges = user.badges().toArray();

          if (badges.length) {
            return <ul className="DiscussionHero-badges badges">{listItems(badges)}</ul>;
          } else {
            return app.translator.trans('core.admin.users.grid.columns.group_badges.no_badges');
          }
        },
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

            emailAddress.attr('aria-hidden', visible ? 'false' : 'true');

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
            <div class="UserList-email" key={user.id()} data-email-shown="false">
              <span class="UserList-emailAddress" aria-hidden="true" onclick={() => setEmailVisibility(true)}>
                {user.email()}
              </span>
              <button
                onclick={toggleEmailVisibility}
                class="Button Button--text UserList-emailIconBtn"
                title={app.translator.trans('core.admin.users.grid.columns.email.visibility_show')}
              >
                {icon('far fa-eye-slash fa-fw', { className: 'icon' })}
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
        name: app.translator.trans('core.admin.users.grid.columns.edit_user.title'),
        content: (user: User) => (
          <Button
            className="Button UserList-editModalBtn"
            title={app.translator.trans('core.admin.users.grid.columns.edit_user.tooltip', { username: user.username() })}
            onclick={() => app.modal.show(EditUserModal, { user })}
          >
            {app.translator.trans('core.admin.users.grid.columns.edit_user.button')}
          </Button>
        ),
      },
      -90
    );

    return columns;
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
   * Asynchronously fetch the next set of users to be rendered.
   *
   * Returns an array of Users, plus the raw API payload.
   *
   * Uses the `this.numPerPage` as the response limit, and automatically calculates the offset required from `pageNumber`.
   *
   * @param pageNumber The **zero-based** page number to load and display
   */
  async loadPage(pageNumber: number) {
    if (pageNumber < 0) pageNumber = 0;

    this.loadingPageNumber = pageNumber;

    app.store
      .find<User[]>('users', {
        filter: { q: this.query },
        page: {
          limit: this.numPerPage,
          offset: pageNumber * this.numPerPage,
        },
      })
      .then((apiData) => {
        // Next link won't be present if there's no more data
        this.moreData = !!apiData.payload?.links?.next;

        let data = apiData;

        // @ts-ignore
        delete data.payload;

        this.pageData = data;
        this.pageNumber = pageNumber;
        this.isLoadingPage = false;

        m.redraw();
      })
      .catch((err: Error) => {
        console.error(err);
        this.pageData = [];
      });
  }

  nextPage() {
    this.isLoadingPage = true;
    this.loadPage(this.pageNumber + 1);
  }

  previousPage() {
    this.isLoadingPage = true;
    this.loadPage(this.pageNumber - 1);
  }

  /**
   * @param page The **1-based** page number
   */
  goToPage(page: number) {
    this.isLoadingPage = true;
    this.loadPage(page - 1);
  }
}
