import ExtensionLinkButton from './ExtensionLinkButton';
import Component from '../../common/Component';
import LinkButton from '../../common/components/LinkButton';
import SelectDropdown from '../../common/components/SelectDropdown';
import getCategorizedExtensions from '../utils/getCategorizedExtensions';
import ItemList from '../../common/utils/ItemList';
import Stream from '../../common/utils/Stream';

export default class AdminNav extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.query = Stream('');
  }

  view() {
    return (
      <SelectDropdown className="AdminNav App-titleControl AdminNav-Main" buttonClassName="Button">
        {this.items().toArray().concat(this.extensionItems().toArray())}
      </SelectDropdown>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.scrollToActive();
  }

  onupdate(vnode) {
    super.onupdate(vnode);

    this.scrollToActive();
  }

  scrollToActive() {
    const children = $('.Dropdown-menu').children('.active');
    const nav = $('#admin-navigation');
    const time = app.previous.type ? 250 : 0;

    if (
      children.length > 0 &&
      (children[0].offsetTop > nav.scrollTop() + nav.outerHeight() || children[0].offsetTop + children[0].offsetHeight < nav.scrollTop())
    ) {
      nav.animate(
        {
          scrollTop: children[0].offsetTop - nav.height() / 2,
        },
        time
      );
    }
  }

  /**
   * Build an item list of main links to show in the admin navigation.
   *
   * @return {ItemList}
   */
  items() {
    const items = new ItemList();

    items.add('category-core', <h4 className="ExtensionListTitle">{app.translator.trans('core.admin.nav.categories.core')}</h4>);

    items.add(
      'dashboard',
      <LinkButton href={app.route('dashboard')} icon="far fa-chart-bar" title={app.translator.trans('core.admin.nav.dashboard_title')}>
        {app.translator.trans('core.admin.nav.dashboard_button')}
      </LinkButton>
    );

    items.add(
      'basics',
      <LinkButton href={app.route('basics')} icon="fas fa-pencil-alt" title={app.translator.trans('core.admin.nav.basics_title')}>
        {app.translator.trans('core.admin.nav.basics_button')}
      </LinkButton>
    );

    items.add(
      'mail',
      <LinkButton href={app.route('mail')} icon="fas fa-envelope" title={app.translator.trans('core.admin.nav.email_title')}>
        {app.translator.trans('core.admin.nav.email_button')}
      </LinkButton>
    );

    items.add(
      'permissions',
      <LinkButton href={app.route('permissions')} icon="fas fa-key" title={app.translator.trans('core.admin.nav.permissions_title')}>
        {app.translator.trans('core.admin.nav.permissions_button')}
      </LinkButton>
    );

    items.add(
      'appearance',
      <LinkButton href={app.route('appearance')} icon="fas fa-paint-brush" title={app.translator.trans('core.admin.nav.appearance_title')}>
        {app.translator.trans('core.admin.nav.appearance_button')}
      </LinkButton>
    );

    items.add(
      'userList',
      <LinkButton href={app.route('users')} icon="fas fa-users" title={app.translator.trans('core.admin.nav.userlist_title')}>
        {app.translator.trans('core.admin.nav.userlist_button')}
      </LinkButton>
    );

    items.add(
      'search',
      <div className="Search-input">
        <input
          className="FormControl SearchBar"
          bidi={this.query}
          type="search"
          placeholder={app.translator.trans('core.admin.nav.search_placeholder')}
        />
      </div>
    );

    return items;
  }

  extensionItems() {
    const items = new ItemList();

    const categorizedExtensions = getCategorizedExtensions();
    const categories = app.extensionCategories;

    Object.keys(categorizedExtensions).map((category) => {
      if (!this.query()) {
        items.add(
          `category-${category}`,
          <h4 className="ExtensionListTitle">{app.translator.trans(`core.admin.nav.categories.${category}`)}</h4>,
          categories[category]
        );
      }

      categorizedExtensions[category].map((extension) => {
        const query = this.query().toUpperCase();
        const title = extension.extra['flarum-extension'].title || '';
        const description = extension.description || '';

        if (!query || title.toUpperCase().includes(query) || description.toUpperCase().includes(query)) {
          items.add(
            `extension-${extension.id}`,
            <ExtensionLinkButton
              href={app.route('extension', { id: extension.id })}
              extensionId={extension.id}
              className="ExtensionNavButton"
              title={description}
            >
              {title}
            </ExtensionLinkButton>,
            categories[category]
          );
        }
      });
    });

    return items;
  }
}
