import type Mithril from 'mithril';
import { ComponentAttrs } from '../Component';
declare type ModdedVnodeAttrs = {
    itemClassName?: string;
    key?: string;
};
declare type ModdedTag = Mithril.Vnode['tag'] & {
    isListItem?: boolean;
    isActive?: (attrs: ComponentAttrs) => boolean;
};
declare type ModdedVnode = Mithril.Vnode<ModdedVnodeAttrs> & {
    itemName?: string;
    itemClassName?: string;
    tag: ModdedTag;
};
declare type ModdedChild = ModdedVnode | string | number | boolean | null | undefined;
declare type ModdedChildArray = ModdedChildren[];
declare type ModdedChildren = ModdedChild | ModdedChildArray;
/**
 * This type represents an element of a list returned by `ItemList.toArray()`,
 * coupled with some static properties used on various components.
 */
export declare type ModdedChildrenWithItemName = ModdedChildren & {
    itemName?: string;
};
/**
 * The `listItems` helper wraps an array of components in the provided tag,
 * stripping out any unnecessary `Separator` components.
 *
 * By default, this tag is an `<li>` tag, but this is customisable through the
 * second function parameter, `customTag`.
 */
export default function listItems<Attrs extends ComponentAttrs>(rawItems: ModdedChildrenWithItemName[], customTag?: VnodeElementTag<Attrs>, attributes?: Attrs): Mithril.Vnode[];
export {};
