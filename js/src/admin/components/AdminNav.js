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
        {this.mainItems().toArray().concat(this.extensionItems().toArray())}
      </SelectDropdown>
    );
  }

  /**
   * Build an item list of main links to show in the admin navigation.
   *
   * @return {ItemList}
   */
  mainItems() {
    const items = new ItemList();

    items.add(
      'dashboard',
      LinkButton.component(
        {
          href: app.route('dashboard'),
          icon: 'far fa-chart-bar',
          className: 'mainLink',
          title: app.translator.trans('core.admin.nav.dashboard_title'),
        },
        app.translator.trans('core.admin.nav.dashboard_button')
      )
    );

    items.add(
      'basics',
      LinkButton.component(
        {
          href: app.route('basics'),
          icon: 'fas fa-pencil-alt',
          title: app.translator.trans('core.admin.nav.basics_title'),
        },
        app.translator.trans('core.admin.nav.basics_button')
      )
    );

    items.add(
      'mail',
      LinkButton.component(
        {
          href: app.route('mail'),
          icon: 'fas fa-envelope',
          className: 'mainLink',
          title: app.translator.trans('core.admin.nav.email_title'),
        },
        app.translator.trans('core.admin.nav.email_button')
      )
    );

    items.add(
      'permissions',
      LinkButton.component(
        {
          href: app.route('permissions'),
          icon: 'fas fa-key',
          className: 'mainLink',
          title: app.translator.trans('core.admin.nav.permissions_title'),
        },
        app.translator.trans('core.admin.nav.permissions_button')
      )
    );

    items.add(
      'appearance',
      LinkButton.component(
        {
          href: app.route('appearance'),
          icon: 'fas fa-paint-brush',
          className: 'mainLink',
          title: app.translator.trans('core.admin.nav.appearance_title'),
        },
        app.translator.trans('core.admin.nav.appearance_button')
      )
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
          `${category} NavDivider`,
          <h4 className="ExtensionListTitle">{app.translator.trans(`core.admin.nav.categories.${category}`)}</h4>,
          categories[category]
        );
      }

      Object.keys(categorizedExtensions[category]).map((id) => {
        const extension = categorizedExtensions[category][id];

        const query = this.query().toUpperCase();

        if (
          !query ||
          extension.extra['flarum-extension'].title.toUpperCase().includes(query) ||
          extension.description.toUpperCase().includes(query)
        ) {
          items.add(
            `${extension.id} ExtensionItem`,
            ExtensionLinkButton.component(
              {
                href: app.route('extension', { id: extension.id }),
                extensionId: extension.id,
                className: 'ExtensionNavButton',
                title: extension.description,
              },
              extension.extra['flarum-extension'].title
            ),
            categories[category]
          );
        }
      });
    });

    return items;
  }
}
