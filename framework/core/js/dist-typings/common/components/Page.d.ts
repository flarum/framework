import type Mithril from 'mithril';
import Component from '../Component';
export interface IPageAttrs {
    key?: number;
    routeName: string;
}
/**
 * The `Page` component
 *
 * @abstract
 */
export default abstract class Page<CustomAttrs extends IPageAttrs = IPageAttrs> extends Component<CustomAttrs> {
    /**
     * A class name to apply to the body while the route is active.
     */
    protected bodyClass: string;
    /**
     * Whether we should scroll to the top of the page when its rendered.
     */
    protected scrollTopOnCreate: boolean;
    /**
     * Whether the browser should restore scroll state on refreshes.
     */
    protected useBrowserScrollRestoration: boolean;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
}
