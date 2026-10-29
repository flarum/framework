import Dropdown, { IDropdownAttrs } from './Dropdown';
import classList from '../utils/classList';
import type Component from '../Component';
import type Mithril from 'mithril';
import Icon from './Icon';

/**
 * Determines via a vnode is currently "active".
 * Due to changes in Mithril 2, attrs will not be instantiated until AFTER view()
 * is initially called on the parent component, so we can not always depend on the
 * active attr to determine which element should be displayed as the "active child".
 *
 * This is a temporary patch, and as so, is not exported / placed in utils.
 */
function isActive(vnode: Mithril.Children): boolean {
  if (!vnode || typeof vnode !== 'object' || vnode instanceof Array) return false;

  const tag = vnode.tag;

  // Allow non-selectable dividers/headers to be added.
  if (typeof tag === 'string' && tag !== 'a' && tag !== 'button') return false;

  if ((typeof tag === 'object' || typeof tag === 'function') && 'initAttrs' in tag) {
    (tag as unknown as typeof Component).initAttrs(vnode.attrs);
  }

  return (typeof tag === 'object' || typeof tag === 'function') && 'isActive' in tag ? (tag as any).isActive(vnode.attrs) : vnode.attrs.active;
}

export interface ISelectDropdownAttrs extends IDropdownAttrs {
  defaultLabel: Mithril.Children;
}

/**
 * The `SelectDropdown` component is the same as a `Dropdown`, except the toggle
 * button's label is set as the label of the first child which has a truthy
 * `active` prop.
 */
export default class SelectDropdown<CustomAttrs extends ISelectDropdownAttrs = ISelectDropdownAttrs> extends Dropdown<CustomAttrs> {
  static initAttrs(attrs: ISelectDropdownAttrs) {
    attrs.caretIcon ??= 'fas fa-sort';

    super.initAttrs(attrs);

    attrs.className = classList(attrs.className, 'Dropdown--select');
  }

  getButtonContent(children: Mithril.ChildArray): Mithril.ChildArray {
    const activeChild = children.find(isActive);
    let label = (activeChild && typeof activeChild === 'object' && 'children' in activeChild && activeChild.children) || this.attrs.defaultLabel;

    return [
      // An icon is what identifies the control once there is no room for its
      // label — without one, hiding the label leaves a caret floating on its
      // own with nothing to say what it selects.
      this.attrs.icon ? <Icon name={this.attrs.icon} noStyleOverride={this.attrs.noStyleOverride} className="Button-icon" /> : null,
      <span className="Button-label">
        <span className="Button-labelText">{label}</span>
      </span>,
      this.attrs.caretIcon ? <Icon name={this.attrs.caretIcon} className="Button-caret" /> : null,
    ];
  }

  /**
   * Which option is in effect is otherwise conveyed only by a class on the item
   * and by the toggle button borrowing that option's label — neither of which a
   * screen reader announces while moving through the menu.
   *
   * `aria-current` rather than `aria-selected`: the latter is only valid on
   * roles this menu does not claim, and claiming them would mean implementing
   * the whole listbox keyboard contract. Unselected items are left without the
   * attribute rather than given `"false"`, since only one item can be current
   * and the absence says as much.
   */
  getMenu(items: Mithril.Vnode<any, any>[]): Mithril.Vnode<any, any> {
    items.forEach((item) => {
      // Each item arrives wrapped in the list element `listItems` built around
      // it, so the option itself is the wrapper's child.
      const option = (Array.isArray(item?.children) ? item.children[0] : item?.children) as Mithril.Children;

      if (isActive(option)) {
        item.attrs = { ...item.attrs, 'aria-current': 'true' };
      }
    });

    return super.getMenu(items);
  }
}
