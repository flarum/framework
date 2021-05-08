import Tooltip from './Tooltip';
import Component from '../Component';
import icon from '../helpers/icon';
import classList from '../utils/classList';

/**
 * The `Badge` component represents a user/discussion badge, indicating some
 * status (e.g. a discussion is stickied, a user is an admin).
 *
 * A badge may have the following special attrs:
 *
 * - `type` The type of badge this is. This will be used to give the badge a
 *   class name of `Badge--{type}`.
 * - `icon` The name of an icon to show inside the badge.
 * - `label`
 *
 * All other attrs will be assigned as attributes on the badge element.
 */
export default class Badge extends Component {
  view() {
    const attrs = Object.assign({}, this.attrs);

    const { type, icon: iconName, title = '' } = attrs;

    const className = classList('Badge', [type && `Badge--${type}`], attrs.className);

    return (
      <Tooltip text={title} {...attrs} className={className}>
        {iconName ? icon(iconName, { className: 'Badge-icon' }) : m.trust('&nbsp;')}
      </Tooltip>
    );
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    if (this.attrs.label) this.$().tooltip();
  }
}
