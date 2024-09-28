import Component, { ComponentAttrs } from '../../common/Component';
import SubtreeRetainer from '../../common/utils/SubtreeRetainer';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
import type User from '../../common/models/User';
export interface IAbstractPostAttrs extends ComponentAttrs {
}
/**
 * This component can be used on any type of model with an author and content.
 * Subclasses are specialized for specific types of models.
 */
export default abstract class AbstractPost<CustomAttrs extends IAbstractPostAttrs = IAbstractPostAttrs> extends Component<CustomAttrs> {
    /**
     * May be set by subclasses.
     */
    loading: boolean;
    /**
     * Ensures that the post will not be redrawn
     * unless new data comes in.
     */
    subtree: SubtreeRetainer;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    view(vnode: Mithril.Vnode<CustomAttrs, this>): JSX.Element;
    onbeforeupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): boolean;
    onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    elementAttrs(): Record<string, unknown>;
    header(): Mithril.Children;
    content(): Mithril.Children[];
    classes(existing?: string): string[];
    actionItems(): ItemList<Mithril.Children>;
    footerItems(): ItemList<Mithril.Children>;
    sideItems(): ItemList<Mithril.Children>;
    abstract user(): User | null | false;
    abstract controls(): Mithril.Children[];
    abstract freshness(): Date;
    abstract createdByStarter(): boolean;
    avatar(): Mithril.Children;
}
