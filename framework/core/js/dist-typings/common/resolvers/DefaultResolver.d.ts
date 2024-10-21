import type Mithril from 'mithril';
import type { AsyncNewComponent, NewComponent, RouteResolver } from '../Application';
import type { ComponentAttrs } from '../Component';
import Component from '../Component';
/**
 * Generates a route resolver for a given component.
 *
 * In addition to regular route resolver functionality:
 * - It provide the current route name as an attr
 * - It sets a key on the component so a rerender will be triggered on route change.
 */
export default class DefaultResolver<Attrs extends ComponentAttrs, Comp extends Component<Attrs & {
    routeName: string;
}>, RouteArgs extends Record<string, unknown> = {}> implements RouteResolver<Attrs, Comp, RouteArgs> {
    component: NewComponent<Comp> | AsyncNewComponent<Comp>;
    routeName: string;
    constructor(component: NewComponent<Comp> | AsyncNewComponent<Comp>, routeName: string);
    /**
     * When a route change results in a changed key, a full page
     * rerender occurs. This method can be overridden in subclasses
     * to prevent rerenders on some route changes.
     */
    makeKey(): string;
    makeAttrs(vnode: Mithril.Vnode<Attrs, Comp>): Attrs & {
        routeName: string;
    };
    onmatch(args: RouteArgs, requestedPath: string, route: string): Promise<NewComponent<Comp>>;
    render(vnode: Mithril.Vnode<Attrs, Comp>): Mithril.Children;
}
