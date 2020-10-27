import ExtensionLinkButton from './ExtensionLinkButton';
import Component from '../../common/Component';
import LinkButton from '../../common/components/LinkButton';
import SelectDropdown from '../../common/components/SelectDropdown';
import ItemList from '../../common/utils/ItemList';

export default class AdminNav extends Component {
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
          title: app.translator.trans('core.admin.nav.appearance_title'),
        },
        app.translator.trans('core.admin.nav.appearance_button')
      )
    );

    items.add(
      'search',
      <div className="Search-input">
        <input className="FormControl SearchBar" type="search"
               placeholder={app.translator.trans('core.admin.nav.search_placeholder')}/>
      </div>
    );

    return items;
  }

  oncreate(vnode) {
    $('.SearchBar').on('keyup', () => {
      const filter = $('.SearchBar').val().toUpperCase();
      const list = $('.Dropdown-menu');
      if (!filter) {
        list.children('li').show();
      } else {
        list.children('li').map((key) => {
          const element = $(list.children('li')[key]);
          const child = $(element.children()[0]);
          if (
            (!element
                .attr('class')
                .replace(/item-|ExtensionItem|active/gi, '')
                .toUpperCase()
                .includes(filter) &&
              !(child && child.attr('title') && child.attr('title').toUpperCase().includes(filter))) &&
            element.attr('class').includes('ExtensionItem') ||
            element.attr('class').includes('NavDivider')
          ) {
            $(list.children('li')[key]).hide();
          } else {
            element.show();
          }
        });
      }
    });
  }

  extensionCategories() {
    return {
      discussion: 70,
      moderation: 60,
      feature: 50,
      formatting: 40,
      theme: 30,
      authentication: 20,
      language: 10,
      other: 0,
    };
  }

  getCategorizedExtensions() {
    let extensions = {};

    Object.keys(app.data.extensions).map((id) => {
      const extension = app.data.extensions[id];
      let category = extension.extra['flarum-extension'].category;

      if (!extension.extra['flarum-extension'].category) {
        category = 'other';
      }

      // Wrap languages packs into new system
      if (extension.extra['flarum-locale']) {
        category = 'language';
      }

      if (category in this.extensionCategories()) {
        extensions[category] = extensions[category] || {};

        extensions[category][id] = extension;
      } else {
        // If the extension doesn't fit
        // into a category add it to other
        extensions.other[id] = extension;
      }
    });

    return extensions;
  }

  extensionItems() {
    const items = new ItemList();

    const categorizedExtensions = this.getCategorizedExtensions();
    const categories = this.extensionCategories();

    Object.keys(categorizedExtensions).map((category) => {
      items.add(
        `${category} NavDivider`,
        <h4 className="ExtensionListTitle">{app.translator.trans(`core.admin.nav.categories.${category}`)}</h4>,
        categories[category]
      );

      Object.keys(categorizedExtensions[category]).map((id) => {
        const extension = categorizedExtensions[category][id];
        items.add(
          `${extension.id} ExtensionItem`,
          ExtensionLinkButton.component(
            {
              href: app.route('extension', {id: extension.id}),
              extensionId: extension.id,
              className: 'ExtensionNavButton',
              title: extension.description,
            },
            extension.extra['flarum-extension'].title
          ),
          categories[category]
        );
      });
    });

    return items;
  }
}
