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
import Checkbox from '../../common/components/Checkbox';
import CreateUserModal from './CreateUserModal';

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
  /**
   * Number of users to load per page.
   */
  private numPerPage: number = 50;

  /**
   * Current page number. Zero-indexed.
   */
  private pageNumber: number = 0;

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
      const indexOfSearch = location.hash.indexOf('?') + 1;
      const search = indexOfSearch >= 0 ? location.hash.substr(indexOfSearch) : '';

      const params = new URLSearchParams(search);
      const num = parseInt(params.get('page') || 'NaN');

      if (num >= 1 && num <= this.getTotalPageCount()) {
        this.loadPage(num - 1);
      } else {
        this.goToPage(0);
      }

      return [
        <section class="UserListPage-grid UserListPage-grid--loading">
          <LoadingIndicator containerClassName="LoadingIndicator--block" size="large" />
        </section>,
      ];
    }

    const columns = this.columns().toArray();

    return [
      this.infoHeader(),
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
      this.pagination(),
    ];
  }

  /**
   * Constructs a list of items that are shown in the info header.
   */
  infoHeaderItems(): ItemList {
    const items = new ItemList();

    items.add(
      'totalUsers',
      <p class="UserListPage-totalUsers">{app.translator.trans('core.admin.users.total_users', { count: this.userCount })}</p>,
      1000
    );

    items.add(
      'copyPageUrl',
      <Button
        icon="fas fa-link"
        class="Button"
        onclick={() => {
          try {
            navigator.clipboard.writeText(`${app.forum.attribute('baseUrl')}/admin#/users?page=${this.pageNumber + 1}`).then(() => {
              const key = app.alerts.show({ type: 'success' }, app.translator.trans('core.admin.users.info_header.copy_page_url.copied'));

              setTimeout(() => app.alerts.dismiss(key), 5000);
            });
          } catch {}
        }}
      >
        {app.translator.trans('core.admin.users.info_header.copy_page_url.button', { pageNumber: this.pageNumber + 1 })}
      </Button>,
      900
    );

    items.add(
      'createUser',
      <Button
        icon="fas fa-user-plus"
        class="Button"
        onclick={() => {
          app.modal.show(CreateUserModal);
        }}
      >
        {app.translator.trans('core.admin.users.info_header.create_user.button')}
      </Button>,
      800
    );

    return items;
  }

  protected infoHeader() {
    const items = this.infoHeaderItems().toArray();

    return <aside class="UserListPage-infoHeader">{items}</aside>;
  }

  protected pagination(): Mithril.Children {
    return [
      <nav>
        <div class="UserListPage-gridPagination">
          <Button
            disabled={this.pageNumber === 0}
            title={app.translator.trans('core.admin.users.pagination.first_button')}
            onclick={this.goToPage.bind(this, 0)}
            icon="fas fa-step-backward"
            className="Button Button--icon UserListPage-nextBtn"
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
              current: this.pageNumber + 1,
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
            title={app.translator.trans('core.admin.users.pagination.last_button')}
            onclick={this.goToPage.bind(this, this.getTotalPageCount() - 1)}
            icon="fas fa-step-forward"
            className="Button Button--icon UserListPage-nextBtn"
          />
        </div>
        <div class="UserListPage-goToPage">
          {/*
            Why do we use a text input instead of a number?
            See: https://technology.blog.gov.uk/2020/02/24/why-the-gov-uk-design-system-team-changed-the-input-type-for-numbers/
          */}
          <label>
            {app.translator.trans('core.admin.users.pagination.go_to_page')}{' '}
            <input class="UserListPage-pageNumberInput FormControl" type="text" inputmode="numeric" pattern="[0-9]*" value={this.pageNumber + 1} />{' '}
            <Button
              class="Button"
              onclick={() => {
                const input = this.$('.UserListPage-pageNumberInput').first();
                const num = parseInt((input.val() as string) || 'NaN');

                if (num >= 1 && num <= this.getTotalPageCount()) {
                  this.loadPage(num - 1);
                }
              }}
            >
              {app.translator.trans('core.admin.users.pagination.go')}
            </Button>
          </label>
        </div>
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
      'displayName',
      {
        name: app.translator.trans('core.admin.users.grid.columns.display_name.title'),
        content: (user: User) => user.displayName(),
      },
      85
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
      'emailConfirmed',
      {
        name: app.translator.trans('core.admin.users.grid.columns.email_confirmed.title'),
        content: (user: User) => {
          return (
            <span class="UserList-emailConfirmed">
              <Checkbox
                state={user.isEmailConfirmed()}
                onchange={(newVal) => {
                  user.save({ isEmailConfirmed: newVal }).then(m.redraw);
                }}
              />
            </span>
          );
        },
      },
      75
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
   * @param pageNumber The page number to load and display
   */
  async loadPage(pageNumber: number) {
    if (pageNumber < 0) pageNumber = 0;

    app.store
      .find<User[]>('users', {
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

  goToPage(pageNumber: number) {
    this.isLoadingPage = true;
    this.loadPage(pageNumber);
  }

  nextPage() {
    this.goToPage(this.pageNumber + 1);
  }

  previousPage() {
    this.goToPage(this.pageNumber - 1);
  }
}
