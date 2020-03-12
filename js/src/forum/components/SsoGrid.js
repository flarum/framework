import Component from '../../common/Component';
import Checkbox from '../../common/components/Checkbox';
import icon from '../../common/helpers/icon';
import ItemList from '../../common/utils/ItemList';

/**
 * The `SsoGrid` component displays a table of linked sso drivers,
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
    this.drivers = this.ssoDrivers().toArray();

    /**
     * A map of Sso type-method combinations to the checkbox instances
     * that represent them.
     *
     * @type {Object}
     */
    this.inputs = {};

    // For each of the Sso type-method combinations, create and store a
    // new checkbox component instance, which we will render in the view.
    this.drivers.forEach((driver) => {
      this.inputs[driver.name] = new Checkbox({
        state: this.driverLinked(driver.name),
        onchange: () => this.toggle(driver.name),
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
          {this.drivers.map((driver) => (
            <tr>
              <td className="SsoGrid-groupToggle">
                {icon(driver.icon)} {driver.label}
              </td>
              <td className="SsoGrid-checkbox">{this.inputs[driver.name].render()}</td>
            </tr>
          ))}
        </tbody>
      </table>
    );
  }

  driverLinked(driver) {
    return app.session.user.data.attributes.ssoDrivers.includes(driver);
  }

  /**
   * Toggle the linked/unlinked state of the given sso driver.
   *
   * @param {Array} keys
   */
  toggle(driver) {
    const control = this.inputs[driver];
    control.loading = true;

    if (this.driverLinked(driver)) {
      m.redraw();

      app
        .request({
          method: 'DELETE',
          url: app.forum.attribute('apiUrl') + '/auth/' + driver,
        })
        .then(() => {
          control.props.state = false;
          control.loading = false;
          app.session.user.data.attributes.ssoDrivers = app.session.user.data.attributes.ssoDrivers.filter((item) => item != driver);
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
        app.forum.attribute('baseUrl') + '/auth/' + driver,
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
   * Build an item list for the drivers to display in the grid.
   *
   * Each driver is an object which has the following properties:
   *
   * - `name` The name of the driver.
   * - `icon` The icon to display in the column header.
   * - `label` The label to display in the column header.
   *
   * @return {ItemList}
   */
  ssoDrivers() {
    const items = new ItemList();

    const drivers = app.forum.data.attributes.ssoDrivers;

    for (const driver in drivers) {
      items.add(driver, {
        name: driver,
        icon: drivers[driver].icon,
        label: drivers[driver].name,
      });
    }

    return items;
  }
}
