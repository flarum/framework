import Component, { type ComponentAttrs } from '../../common/Component';
import ScrollListener from '../../common/utils/ScrollListener';
import ItemList from '../../common/utils/ItemList';
import type Mithril from 'mithril';
import type PostStreamState from '../states/PostStreamState';
export interface IPostStreamScrubberAttrs extends ComponentAttrs {
    stream: PostStreamState;
    className?: string;
}
export interface PostStreamScrubberUpdateOptions {
    fromScroll?: boolean;
    forceHeightChange?: boolean;
    animate?: boolean;
}
export interface PostStreamScrubberPercentPerPost {
    /**
     * Per post outside the handle.
     */
    index: number;
    /**
     * Per post inside the handle, which is larger while the handle is held at
     * its minimum height.
     */
    visible: number;
}
/**
 * What core has set on one element outside of rendering, and for which part.
 */
interface SyncedValues {
    part: string;
    styles: Record<string, string>;
    text?: Node | null;
}
export default class PostStreamScrubber<CustomAttrs extends IPostStreamScrubberAttrs = IPostStreamScrubberAttrs> extends Component<CustomAttrs> {
    /**
     * The smallest height, in pixels, the handle shrinks to in long discussions.
     */
    static minHandleHeight: number;
    stream: PostStreamState;
    handlers: {
        onresize?: () => void;
        onmousemove?: (e: JQuery.TriggeredEvent) => void;
        onmouseup?: () => void;
    };
    scrollListener: ScrollListener;
    dragging: boolean;
    mouseStart: number;
    indexStart: number;
    adjustingHeight: boolean;
    showing: boolean;
    closeWatcher?: CloseWatcher;
    /**
     * The synced parts as of the last check, by element.
     */
    protected syncedParts: Map<Element, string>;
    /**
     * What core has set on each element outside of rendering.
     */
    protected syncedValues: Map<Element, SyncedValues>;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    view(): JSX.Element;
    viewItems(): ItemList<Mithril.Children>;
    scrubberItems(): ItemList<Mithril.Children>;
    scrollbarItems(): ItemList<Mithril.Children>;
    infoItems(): ItemList<Mithril.Children>;
    viewingText(): Mithril.Children;
    firstPostLabel(): Mithril.Children;
    unreadLabel(unreadCount: number): Mithril.Children;
    lastPostLabel(): Mithril.Children;
    onupdate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    oncreate(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    /**
     * Sync the handle and the info text with the stream's `index` and `visible`.
     */
    updateScrubberValues(options?: PostStreamScrubberUpdateOptions): void;
    goToFirst(): void;
    goToLast(): void;
    /**
     * Fill the height of the sidebar without overlapping the footer.
     */
    onresize(): void;
    onmousedown(e: JQuery.TriggeredEvent & {
        redraw?: boolean;
    }): void;
    onmousemove(e: JQuery.TriggeredEvent): void;
    onmouseup(): void;
    /**
     * Jump so that the handle ends up centred on the click.
     */
    onclick(e: JQuery.TriggeredEvent): void;
    /**
     * Take back what core set on elements that no longer play the part it was
     * set for, and report whether any part is now played by a different element.
     */
    protected reconcileSyncedParts(vnode: Mithril.Vnode<CustomAttrs, this>): boolean;
    /**
     * Remember what core sets on a part outside of rendering.
     *
     * Mithril runs this component's onupdate before its children's, so an element
     * that changed parts has been released before anything is set on it again.
     */
    protected syncValues(element: Element, part: string, values: Omit<SyncedValues, 'part'>): void;
    protected syncText(part: string, text: string): void;
    /**
     * Take back the styles and text core set on an element, except for the
     * styles the vnode now rendering it declares, which Mithril has just set.
     */
    protected releaseValues(element: Element, values: SyncedValues, vnode?: Mithril.Vnode): void;
    /**
     * The share of the track's height, in percent, that each post takes up.
     */
    percentPerPost(): PostStreamScrubberPercentPerPost;
}
export {};
