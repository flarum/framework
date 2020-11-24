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

  /**
   * Build an item list of main links to show in the admin navigation.
   *
   * @return {ItemList}
   */
  items() {
    const items = new ItemList();

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
          category,
          <h4 className="ExtensionListTitle">{app.translator.trans(`core.admin.nav.categories.${category}`)}</h4>,
          categories[category]
        );
      }

      categorizedExtensions[category].map((extension) => {
        const query = this.query().toUpperCase();
        const title = extension.extra['flarum-extension'].title;

        if (!query || title.toUpperCase().includes(query) || extension.description.toUpperCase().includes(query)) {
          items.add(
            extension.id,
            <ExtensionLinkButton
              href={app.route('extension', { id: extension.id })}
              extensionId={extension.id}
              className="ExtensionNavButton"
              title={extension.description}
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
