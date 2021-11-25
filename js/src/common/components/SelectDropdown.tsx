import type Mithril from 'mithril';
import Dropdown, { IDropdownAttrs } from './Dropdown';
import icon from '../helpers/icon';
import { ModdedVnode } from '../helpers/listItems';

/**
 * Determines via a vnode is currently "active".
 * Due to changes in Mithril 2, attrs will not be instantiated until AFTER view()
 * is initially called on the parent component, so we can not always depend on the
 * active attr to determine which element should be displayed as the "active child".
 *
 * This is a temporary patch, and as so, is not exported / placed in utils.
 */
function isActive(vnode: ModdedVnode<{}>) {
  const tag = vnode.tag as VnodeElementTag;

  // Allow non-selectable dividers/headers to be added.
  if (typeof tag === 'string' && tag !== 'a' && tag !== 'button') return false;

  if ('initAttrs' in tag) {
    tag.initAttrs(vnode.attrs);
  }

  return 'isActive' in tag ? tag.isActive(vnode.attrs) : (vnode.attrs as any).active;
}

export interface ISelectDropdownAttrs extends IDropdownAttrs {
  /**
   * An icon for the select dropdown's caret.
   */
  caretIcon?: string;

  /**
   * The default label if no child is active.
   */
  defaultLabel?: Mithril.Children;

}

/**
 * The `SelectDropdown` component is the same as a `Dropdown`, except the toggle
 * button's label is set as the label of the first child which has a truthy
 * `active` prop.
 */
export default class SelectDropdown<CustomAttrs extends ISelectDropdownAttrs = ISelectDropdownAttrs> extends Dropdown<CustomAttrs> {
  static initAttrs(attrs: ISelectDropdownAttrs) {
    attrs.caretIcon = typeof attrs.caretIcon !== 'undefined' ? attrs.caretIcon : 'fas fa-sort';

    super.initAttrs(attrs);

    attrs.className += ' Dropdown--select';
  }

  protected getButtonContent(children: Mithril.Children): Mithril.ChildArray {
    const activeChild = Array.isArray(children) ? children.find(isActive) : children;
    let label = (activeChild && typeof activeChild === 'object' && 'children' in activeChild && activeChild.children) || this.attrs.defaultLabel;

    if (label instanceof Array) label = label[0];

    return [<span className="Button-label">{label}</span>, icon(this.attrs.caretIcon!, { className: 'Button-caret' })];
  }
}
