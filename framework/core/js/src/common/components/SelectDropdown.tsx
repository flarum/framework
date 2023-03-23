import Dropdown, { IDropdownAttrs } from './Dropdown';
import icon from '../helpers/icon';
import classList from '../utils/classList';
import type Component from '../Component';
import type Mithril from 'mithril';

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
  defaultLabel: string;
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

    // @ts-ignore
    if (Array.isArray(label)) label = label[0];

    return [<span className="Button-label">{label}</span>, this.attrs.caretIcon ? icon(this.attrs.caretIcon, { className: 'Button-caret' }) : null];
  }
}
