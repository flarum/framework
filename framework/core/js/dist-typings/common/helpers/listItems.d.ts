import * as Mithril from 'mithril';
/**
 * The `listItems` helper wraps a collection of components in <li> tags,
 * stripping out any unnecessary `Separator` components.
 */
export default function listItems(items: Mithril.Vnode | Array<Mithril.Vnode>): Array<Mithril.Vnode>;
