import Component from '../Component';
import icon from '../helpers/icon';
import extract from '../utils/extract';

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
    const type = extract(attrs, 'type');
    const iconName = extract(attrs, 'icon');

    attrs.className = 'Badge ' + (type ? 'Badge--' + type : '') + ' ' + (attrs.className || '');
    attrs.title = extract(attrs, 'label') || '';

    return <span {...attrs}>{iconName ? icon(iconName, { className: 'Badge-icon' }) : m.trust('&nbsp;')}</span>;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    if (this.attrs.label) this.$().tooltip();
  }
}
