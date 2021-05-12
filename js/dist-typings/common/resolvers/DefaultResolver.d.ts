import Mithril from 'mithril';
/**
 * Generates a route resolver for a given component.
 * In addition to regular route resolver functionality:
 * - It provide the current route name as an attr
 * - It sets a key on the component so a rerender will be triggered on route change.
 */
export default class DefaultResolver {
    component: Mithril.Component;
    routeName: string;
    constructor(component: any, routeName: any);
    /**
     * When a route change results in a changed key, a full page
     * rerender occurs. This method can be overriden in subclasses
     * to prevent rerenders on some route changes.
     */
    makeKey(): string;
    makeAttrs(vnode: any): any;
    onmatch(args: any, requestedPath: any, route: any): Mithril.Component<{}, {}>;
    render(vnode: any): any[];
}
