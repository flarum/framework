import Page from '../../common/components/Page';
import ItemList from '../../common/utils/ItemList';
import UserCard from './UserCard';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import SelectDropdown from '../../common/components/SelectDropdown';
import LinkButton from '../../common/components/LinkButton';
import Separator from '../../common/components/Separator';
import listItems from '../../common/helpers/listItems';
import AffixedSidebar from './AffixedSidebar';

/**
 * The `UserPage` component shows a user's profile. It can be extended to show
 * content inside of the content area. See `ActivityPage` and `SettingsPage` for
 * examples.
 *
 * @abstract
 */
export default class UserPage extends Page {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * The user this page is for.
     *
     * @type {User}
     */
    this.user = null;

    this.bodyClass = 'App--user';
  }

  view() {
    return (
      <div className="UserPage">
        {this.user
          ? [
              <UserCard
                user={this.user}
                className="Hero UserHero"
                editable={this.user.canEdit() || this.user === app.session.user}
                controlsButtonClassName="Button"
              />,
              <div className="container">
                <div className="sideNavContainer">
                  <AffixedSidebar>
                    <nav className="sideNav UserPage-nav">
                      <ul>{listItems(this.sidebarItems().toArray())}</ul>
                    </nav>
                  </AffixedSidebar>
                  <div className="sideNavOffset UserPage-content">{this.content()}</div>
                </div>
              </div>,
            ]
          : [<LoadingIndicator display="block" />]}
      </div>
    );
  }

  /**
   * Get the content to display in the user page.
   *
   * @return {VirtualElement}
   */
  content() {}

  /**
   * Initialize the component with a user, and trigger the loading of their
   * activity feed.
   *
   * @param {User} user
   * @protected
   */
  show(user) {
    this.user = user;

    app.current.set('user', user);

    app.setTitle(user.displayName());

    m.redraw();
  }

  /**
   * Given a username, load the user's profile from the store, or make a request
   * if we don't have it yet. Then initialize the profile page with that user.
   *
   * @param {String} username
   */
  loadUser(username) {
    const lowercaseUsername = username.toLowerCase();

    // Load the preloaded user object, if any, into the global app store
    // We don't use the output of the method because it returns raw JSON
    // instead of the parsed models
    app.preloadedApiDocument();

    app.store.all('users').some((user) => {
      if ((user.username().toLowerCase() === lowercaseUsername || user.id() === username) && user.joinTime()) {
        this.show(user);
        return true;
      }
    });

    if (!this.user) {
      app.store.find('users', username, { bySlug: true }).then(this.show.bind(this));
    }
  }

  /**
   * Build an item list for the content of the sidebar.
   *
   * @return {ItemList}
   */
  sidebarItems() {
    const items = new ItemList();

    items.add(
      'nav',
      <SelectDropdown className="App-titleControl" buttonClassName="Button">
        {this.navItems().toArray()}
      </SelectDropdown>
    );

    return items;
  }

  /**
   * Build an item list for the navigation in the sidebar.
   *
   * @return {ItemList}
   */
  navItems() {
    const items = new ItemList();
    const user = this.user;

    items.add(
      'posts',
      <LinkButton href={app.route('user.posts', { username: user.slug() })} icon="far fa-comment">
        {app.translator.trans('core.forum.user.posts_link')} <span className="Button-badge">{user.commentCount()}</span>
      </LinkButton>,
      100
    );

    items.add(
      'discussions',
      <LinkButton href={app.route('user.discussions', { username: user.slug() })} icon="fas fa-bars">
        {app.translator.trans('core.forum.user.discussions_link')} <span className="Button-badge">{user.discussionCount()}</span>
      </LinkButton>,
      90
    );

    if (app.session.user === user) {
      items.add('separator', <Separator />, -90);
      items.add(
        'settings',
        <LinkButton href={app.route('settings')} icon="fas fa-cog">
          {app.translator.trans('core.forum.user.settings_link')}
        </LinkButton>,
        -100
      );
    }

    return items;
  }
}
