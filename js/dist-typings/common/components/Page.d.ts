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
    oninit(vnode: any): void;
    oncreate(vnode: any): void;
    onremove(vnode: any): void;
}
