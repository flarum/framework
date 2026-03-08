import app from '../../admin/app';
import ExtensionLinkButton from './ExtensionLinkButton';
import Component from '../../common/Component';
import LinkButton from '../../common/components/LinkButton';
import SelectDropdown from '../../common/components/SelectDropdown';
import getCategorizedExtensions from '../utils/getCategorizedExtensions';
import ItemList from '../../common/utils/ItemList';
import Stream from '../../common/utils/Stream';
import Input from '../../common/components/Input';
import Icon from '../../common/components/Icon';
import extractText from '../../common/utils/extractText';

export default class AdminNav extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    this.query = Stream('');
    this.collapsed = {};

    // Pre-expand the category of the currently active extension.
    // m.route.get() may be null on a hard reload before Mithril has processed
    // the hash, so fall back to parsing window.location.hash directly.
    const currentRoute = m.route.get() || window.location.hash.replace(/^#/, '');
    const extensionMatch = currentRoute.match(/\/extensions?\/([^/?]+)/);
    if (extensionMatch) {
      const activeId = extensionMatch[1];
      const categorized = getCategorizedExtensions();
      for (const [category, extensions] of Object.entries(categorized)) {
        if (extensions.some((ext) => ext.id === activeId)) {
          this.collapsed[category] = false;
          break;
        }
      }
    }
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

  isCollapsed(category) {
    if (this.query()) return false;
    return this.collapsed[category] !== false;
  }

  toggleCollapsed(category) {
    this.collapsed[category] = !this.isCollapsed(category);
    m.redraw();
  }

  /**
   * Build an item list of main links to show in the admin navigation.
   *
   * @return {ItemList<import('mithril').Children>}
   */
  items() {
    const items = new ItemList();

    items.add('category-core', <h4 className="ExtensionListTitle">{app.translator.trans('core.admin.nav.categories.core')}</h4>, 120);

    items.add(
      'dashboard',
      <LinkButton href={app.route('dashboard')} icon="far fa-chart-bar" title={app.translator.trans('core.admin.nav.dashboard_title')}>
        {app.translator.trans('core.admin.nav.dashboard_button')}
      </LinkButton>,
      100
    );

    items.add(
      'basics',
      <LinkButton href={app.route('basics')} icon="fas fa-pencil-alt" title={app.translator.trans('core.admin.nav.basics_title')}>
        {app.translator.trans('core.admin.nav.basics_button')}
      </LinkButton>,
      90
    );

    items.add(
      'mail',
      <LinkButton href={app.route('mail')} icon="fas fa-envelope" title={app.translator.trans('core.admin.nav.email_title')}>
        {app.translator.trans('core.admin.nav.email_button')}
      </LinkButton>,
      80
    );

    items.add(
      'permissions',
      <LinkButton href={app.route('permissions')} icon="fas fa-key" title={app.translator.trans('core.admin.nav.permissions_title')}>
        {app.translator.trans('core.admin.nav.permissions_button')}
      </LinkButton>,
      70
    );

    items.add(
      'appearance',
      <LinkButton href={app.route('appearance')} icon="fas fa-paint-brush" title={app.translator.trans('core.admin.nav.appearance_title')}>
        {app.translator.trans('core.admin.nav.appearance_button')}
      </LinkButton>,
      60
    );

    items.add(
      'userList',
      <LinkButton href={app.route('users')} icon="fas fa-users" title={app.translator.trans('core.admin.nav.userlist_title')}>
        {app.translator.trans('core.admin.nav.userlist_button')}
      </LinkButton>,
      50
    );

    if (app.data.settings.show_advanced_settings) {
      items.add(
        'advanced',
        <LinkButton href={app.route('advanced')} icon="fas fa-cog" title={app.translator.trans('core.admin.nav.advanced_title')}>
          {app.translator.trans('core.admin.nav.advanced_button')}
        </LinkButton>,
        40
      );
    }

    items.add(
      'search',
      <Input
        type="search"
        className="SearchBar"
        stream={this.query}
        clearable={true}
        placeholder={extractText(app.translator.trans('core.admin.nav.search_placeholder'))}
      />,
      0
    );

    return items;
  }

  categoryIcon(category) {
    const icons = {
      analytics: 'fas fa-chart-bar',
      authentication: 'fas fa-lock',
      discussion: 'fas fa-comments',
      feature: 'fas fa-star',
      formatting: 'fas fa-paragraph',
      infrastructure: 'fas fa-server',
      language: 'fas fa-language',
      moderation: 'fas fa-shield-alt',
      other: 'fas fa-cube',
      theme: 'fas fa-paint-brush',
    };
    return icons[category] || 'fas fa-puzzle-piece';
  }

  extensionItems() {
    const items = new ItemList();

    const categorizedExtensions = getCategorizedExtensions();
    const categories = app.extensionCategories;
    const query = this.query().toUpperCase();

    Object.keys(categorizedExtensions).map((category) => {
      const extensions = categorizedExtensions[category];
      const count = extensions.length;

      // When searching, only show categories that have matching results
      const matchingExtensions = extensions.filter((extension) => {
        if (!query) return true;
        const title = extension.extra['flarum-extension'].title || '';
        const description = extension.description || '';
        return title.toUpperCase().includes(query) || description.toUpperCase().includes(query);
      });

      if (query && matchingExtensions.length === 0) return;

      const isOpen = !this.isCollapsed(category);

      const abandonedInCategory = extensions.filter((ext) => ext.abandoned);
      const hasDanger = abandonedInCategory.some((ext) => typeof ext.abandoned === 'string');
      const categoryBadgeType = abandonedInCategory.length > 0 ? (hasDanger ? 'danger' : 'warning') : null;

      items.add(
        `category-${category}`,
        <button
          className="ExtensionListTitle"
          onclick={(e) => {
            e.stopPropagation();
            this.toggleCollapsed(category);
          }}
        >
          <Icon name={this.categoryIcon(category)} className="ExtensionListTitle-icon" />
          <span className="ExtensionListTitle-label">{app.translator.trans(`core.admin.nav.categories.${category}`)}</span>
          {categoryBadgeType && <span className={`Badge Badge--${categoryBadgeType} ExtensionListTitle-badge`}>!</span>}
          <span className="ExtensionListTitle-count">{count}</span>
          <Icon name={`fas fa-chevron-${isOpen ? 'down' : 'right'}`} className="ExtensionListTitle-chevron" />
        </button>,
        categories[category]
      );

      if (!isOpen) return;

      matchingExtensions.map((extension) => {
        const title = extension.extra['flarum-extension'].title || '';
        const description = extension.description || '';
        const isAbandoned = extension.abandoned;
        const hasReplacement = typeof isAbandoned === 'string';

        items.add(
          `extension-${extension.id}`,
          <ExtensionLinkButton
            href={app.route('extension', { id: extension.id })}
            extensionId={extension.id}
            className="ExtensionNavButton"
            title={description}
          >
            {title}
            {isAbandoned && <span className={`Badge Badge--${hasReplacement ? 'danger' : 'warning'}`}>!</span>}
          </ExtensionLinkButton>,
          categories[category]
        );
      });
    });

    return items;
  }
}
