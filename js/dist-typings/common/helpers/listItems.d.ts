import type Mithril from 'mithril';
import type * as Component from '../Component';
export interface ModdedVnodeAttrs {
    itemClassName?: string;
    key?: string;
}
export declare type ModdedVnode<Attrs> = Mithril.Vnode<ModdedVnodeAttrs, Component.default<Attrs> | {}> & {
    itemName?: string;
    itemClassName?: string;
};
/**
 * The `listItems` helper wraps an array of components in the provided tag,
 * stripping out any unnecessary `Separator` components.
 *
 * By default, this tag is an `<li>` tag, but this is customisable through the
 * second function parameter, `customTag`.
 */
export default function listItems<Attrs extends Record<string, unknown>>(items: ModdedVnode<Attrs> | ModdedVnode<Attrs>[], customTag?: string | Component.default<Attrs>, attributes?: Attrs): Mithril.Vnode[];
