import app from '../../forum/app';
import Component from '../../common/Component';
import Checkbox from '../../common/components/Checkbox';
import icon from '../../common/helpers/icon';
import ItemList from '../../common/utils/ItemList';

/**
 * The `NotificationGrid` component displays a table of notification types and
 * methods, allowing the user to toggle each combination.
 *
 * ### Attrs
 *
 * - `user`
 */
export default class NotificationGrid extends Component {
  oninit(vnode) {
    super.oninit(vnode);

    /**
     * Information about the available notification methods.
     *
     * @type {({ name: string, icon: string, label: import('mithril').Children })[]}
     */
    this.methods = this.notificationMethods().toArray();

    /**
     * A map of which notification checkboxes are loading.
     *
     * @type {Record<string, boolean>}
     */
    this.loading = {};

    /**
     * Information about the available notification types.
     *
     * @type {({ name: string, icon: string, label: import('mithril').Children })[]}
     */
    this.types = this.notificationTypes().toArray();
  }

  view() {
    const preferences = this.attrs.user.preferences();

    return (
      <table className="NotificationGrid">
        <thead>
          <tr>
            <td />
            {this.methods.map((method) => (
              <th className="NotificationGrid-groupToggle" onclick={this.toggleMethod.bind(this, method.name)}>
                {icon(method.icon)} {method.label}
              </th>
            ))}
          </tr>
        </thead>

        <tbody>
          {this.types.map((type) => (
            <tr>
              <td className="NotificationGrid-groupToggle" onclick={this.toggleType.bind(this, type.name)}>
                {icon(type.icon)} {type.label}
              </td>
              {this.methods.map((method) => {
                const key = this.preferenceKey(type.name, method.name);

                return (
                  <td className="NotificationGrid-checkbox">
                    <Checkbox
                      state={!!preferences[key]}
                      loading={this.loading[key]}
                      disabled={!(key in preferences)}
                      onchange={this.toggle.bind(this, [key])}
                    >
                      <span className="sr-only">
                        {app.translator.trans('core.forum.settings.notification_checkbox_a11y_label_template', {
                          description: type.label,
                          method: method.label,
                        })}
                      </span>
                    </Checkbox>
                  </td>
                );
              })}
            </tr>
          ))}
        </tbody>
      </table>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    this.$('thead .NotificationGrid-groupToggle').bind('mouseenter mouseleave', function (e) {
      const i = parseInt($(this).index(), 10) + 1;
      $(this)
        .parents('table')
        .find('td:nth-child(' + i + ')')
        .toggleClass('highlighted', e.type === 'mouseenter');
    });

    this.$('tbody .NotificationGrid-groupToggle').bind('mouseenter mouseleave', function (e) {
      $(this)
        .parent()
        .find('td')
        .toggleClass('highlighted', e.type === 'mouseenter');
    });
  }

  /**
   * Toggle the state of the given preferences, based on the value of the first
   * one.
   *
   * @param {string[]} keys
   */
  toggle(keys) {
    const user = this.attrs.user;
    const preferences = user.preferences();
    const enabled = !preferences[keys[0]];

    keys.forEach((key) => {
      this.loading[key] = true;
      preferences[key] = enabled;
    });

    m.redraw();

    user.save({ preferences }).then(() => {
      keys.forEach((key) => (this.loading[key] = false));

      m.redraw();
    });
  }

  /**
   * Toggle all notification types for the given method.
   *
   * @param {string} method
   */
  toggleMethod(method) {
    const keys = this.types.map((type) => this.preferenceKey(type.name, method)).filter((key) => key in this.attrs.user.preferences());

    this.toggle(keys);
  }

  /**
   * Toggle all notification methods for the given type.
   *
   * @param {string} type
   */
  toggleType(type) {
    const keys = this.methods.map((method) => this.preferenceKey(type, method.name)).filter((key) => key in this.attrs.user.preferences());

    this.toggle(keys);
  }

  /**
   * Get the name of the preference key for the given notification type-method
   * combination.
   *
   * @param {string} type
   * @param {string} method
   * @return {string}
   */
  preferenceKey(type, method) {
    return 'notify_' + type + '_' + method;
  }

  /**
   * Build an item list for the notification methods to display in the grid.
   *
   * Each notification method is an object which has the following properties:
   *
   * - `name` The name of the notification method.
   * - `icon` The icon to display in the column header.
   * - `label` The label to display in the column header.
   *
   * @return {ItemList<{ name: string, icon: string, label: import('mithril').Children }>}
   */
  notificationMethods() {
    const items = new ItemList();

    items.add('alert', {
      name: 'alert',
      icon: 'fas fa-bell',
      label: app.translator.trans('core.forum.settings.notify_by_web_heading'),
    });

    items.add('email', {
      name: 'email',
      icon: 'far fa-envelope',
      label: app.translator.trans('core.forum.settings.notify_by_email_heading'),
    });

    return items;
  }

  /**
   * Build an item list for the notification types to display in the grid.
   *
   * Each notification type is an object which has the following properties:
   *
   * - `name` The name of the notification type.
   * - `icon` The icon to display in the notification grid row.
   * - `label` The label to display in the notification grid row.
   *
   * @return {ItemList<{ name: string, icon: string, label: import('mithril').Children}>}
   */
  notificationTypes() {
    const items = new ItemList();

    items.add('discussionRenamed', {
      name: 'discussionRenamed',
      icon: 'fas fa-pencil-alt',
      label: app.translator.trans('core.forum.settings.notify_discussion_renamed_label'),
    });

    return items;
  }
}
