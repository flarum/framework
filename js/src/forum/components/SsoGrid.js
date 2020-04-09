import Component from '../../common/Component';
import Checkbox from '../../common/components/Checkbox';
import icon from '../../common/helpers/icon';
import ItemList from '../../common/utils/ItemList';

/**
 * The `SsoGrid` component displays a table of linked sso providers,
 * allowing the user to link/unlink each one to their account.
 *
 */
export default class SsoGrid extends Component {
  init() {
    /**
     * Information about the available Sso methods.
     *
     * @type {Array}
     */
    this.providers = this.ssoProviders().toArray();

    /**
     * A map of Sso type-method combinations to the checkbox instances
     * that represent them.
     *
     * @type {Object}
     */
    this.inputs = {};

    // For each of the Sso type-method combinations, create and store a
    // new checkbox component instance, which we will render in the view.
    this.providers.forEach((provider) => {
      this.inputs[provider.name] = Checkbox.component({
        state: this.providerLinked(provider.name),
        disabled: this.disableUnlinking(provider.name),
        onchange: () => this.toggle(provider.name),
      });
    });
  }

  view() {
    return (
      <table className="SsoGrid">
        <thead>
          <tr>
            <td />
            <th className="SsoGrid-groupToggle">{app.translator.trans('core.forum.settings.sso.linked_header')}</th>
          </tr>
        </thead>

        <tbody>
          {this.providers.map((provider) => (
            <tr>
              <td className="SsoGrid-groupToggle">
                {icon(provider.icon)} {provider.label}
              </td>
              <td className="SsoGrid-checkbox">{this.inputs[provider.name]}</td>
            </tr>
          ))}
        </tbody>
      </table>
    );
  }

  providerLinked(provider) {
    return app.session.user.ssoProviders().includes(provider);
  }

  disableUnlinking(provider) {
    return this.providerLinked(provider) && !app.forum.attribute('enablePasswordAuth') && app.session.user.ssoProviders().length === 1;
  }

  /**
   * Toggle the linked/unlinked state of the given sso provider.
   *
   * @param {Array} keys
   */
  toggle(provider) {
    const control = this.inputs[provider];
    control.loading = true;

    if (this.providerLinked(provider)) {
      m.redraw();

      app
        .request({
          method: 'DELETE',
          url: app.forum.attribute('apiUrl') + '/auth/' + provider,
        })
        .then(() => {
          control.props.state = false;
          control.loading = false;
          app.session.user.data.attributes.ssoProviders = app.session.user.ssoProviders().filter((item) => item != provider);
          for (const input in this.inputs) {
            this.inputs[input].props.disabled = this.disableUnlinking(input);
          }
          m.redraw();
        })
        .catch(() => {
          control.loading = false;
          m.redraw();
        });
    } else {
      const width = 580;
      const height = 400;
      const $window = $(window);

      window.open(
        app.forum.attribute('baseUrl') + '/auth/' + provider,
        'logInPopup',
        `width=${width},` +
          `height=${height},` +
          `top=${$window.height() / 2 - height / 2},` +
          `left=${$window.width() / 2 - width / 2},` +
          'status=no,scrollbars=yes,resizable=no'
      );
      control.loading = false;
      m.redraw();
    }
  }

  /**
   * Build an item list for the providers to display in the grid.
   *
   * Each provider is an object which has the following properties:
   *
   * - `name` The name of the provider.
   * - `icon` The icon to display in the column header.
   * - `label` The label to display in the column header.
   *
   * @return {ItemList}
   */
  ssoProviders() {
    const items = new ItemList();

    const providers = app.forum.data.attributes.ssoProviders;

    for (const provider in providers) {
      items.add(provider, {
        name: provider,
        icon: providers[provider].icon,
        label: providers[provider].name,
      });
    }

    return items;
  }
}
