import Tooltip from './Tooltip';
import Component, { ComponentAttrs } from '../Component';
import icon from '../helpers/icon';
import classList from '../utils/classList';
import textContrastClass from '../helpers/textContrastClass';

export interface IBadgeAttrs extends ComponentAttrs {
  icon: string;
  type?: string;
  label?: string;
  color?: string;
}

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
export default class Badge<CustomAttrs extends IBadgeAttrs = IBadgeAttrs> extends Component<CustomAttrs> {
  view() {
    const { type, icon: iconName, label, color, style = {}, ...attrs } = this.attrs;

    const className = classList('Badge', [type && `Badge--${type}`], attrs.className, textContrastClass(color));

    const iconChild = iconName ? icon(iconName, { className: 'Badge-icon' }) : m.trust('&nbsp;');

    const newStyle = { ...style, '--badge-bg': color };

    const badgeAttrs = {
      ...attrs,
      className,
      style: newStyle,
    };

    const badgeNode = <div {...badgeAttrs}>{iconChild}</div>;

    // If we don't have a tooltip label, don't render the tooltip component.
    if (!label) return badgeNode;

    return <Tooltip text={label}>{badgeNode}</Tooltip>;
  }
}
