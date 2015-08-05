import Component from 'flarum/Component';
import SelectDropdown from 'flarum/components/SelectDropdown';
import Button from 'flarum/components/Button';
import ItemList from 'flarum/utils/ItemList';
import listItems from 'flarum/helpers/listItems';

/**
 * The `FooterSecondary` component displays secondary footer controls, such as
 * the 'Powered by Flarum' message. On the default skin, these are shown on the
 * right side of the footer.
 */
export default class FooterSecondary extends Component {
  view() {
    return (
      <ul className="Footer-controls">
        {listItems(this.items().toArray())}
      </ul>
    );
  }

  /**
   * Build an item list for the controls.
   *
   * @return {ItemList}
   */
  items() {
    const items = new ItemList();

    if (Object.keys(app.locales).length > 1) {
      const locales = [];

      for (const locale in app.locales) {
        locales.push(Button.component({
          active: app.locale === locale,
          children: app.locales[locale],
          icon: app.locale === locale ? 'check' : true,
          onclick: () => {
            if (app.session.user) {
              app.session.user.savePreferences({locale}).then(() => window.location.reload());
            } else {
              document.cookie = `locale=${locale}; path=/; expires=Tue, 19 Jan 2038 03:14:07 GMT`;
              window.location.reload();
            }
          }
        }));
      }

      items.add('locale', SelectDropdown.component({
        children: locales,
        buttonClassName: 'Button Button--text'
      }));
    }

    items.add('poweredBy', (
      <a href="http://flarum.org?r=forum" target="_blank">
        {app.trans('core.powered_by_flarum')}
      </a>
    ));

    return items;
  }
}
