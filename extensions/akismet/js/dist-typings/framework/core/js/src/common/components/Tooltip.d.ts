import Component from '../Component';
import type Mithril from 'mithril';
export interface TooltipAttrs extends Mithril.CommonAttributes<TooltipAttrs, Tooltip> {
    /**
     * Tooltip textual content.
     *
     * String arrays, like those provided by the translator, will be flattened
     * into strings.
     */
    text: string | string[];
    /**
     * Use to manually show or hide the tooltip. `undefined` will show based on cursor events.
     *
     * Default: `undefined`.
     */
    tooltipVisible?: boolean;
    /**
     * Whether to show on focus.
     *
     * Default: `true`.
     */
    showOnFocus?: boolean;
    /**
     * Tooltip position around element.
     *
     * Default: `'top'`.
     */
    position?: 'top' | 'bottom' | 'left' | 'right';
    /**
     * Whether HTML content is allowed in the tooltip.
     *
     * **Warning:** this is a possible XSS attack vector. This option shouldn't
     * be used wherever possible, and may not work when we migrate to another
     * tooltip library. Be prepared for this to break in Flarum stable.
     *
     * Default: `false`.
     *
     * @deprecated
     */
    html?: boolean;
    /**
     * Sets the delay between a trigger state occurring and the tooltip appearing
     * on-screen.
     *
     * **Warning:** this option may be removed when switching to another tooltip
     * library. Be prepared for this to break in Flarum stable.
     *
     * Default: `0`.
     *
     * @deprecated
     */
    delay?: number;
    /**
     * Used to disable the warning for passing text to the `title` attribute.
     *
     * Tooltip text should be passed to the `text` attribute.
     */
    ignoreTitleWarning?: boolean;
}
/**
 * The `Tooltip` component is used to create a tooltip for an element. It
 * requires a single child element to be passed to it. Passing multiple
 * children or fragments will throw an error.
 *
 * You should use this for any tooltips you create to allow for backwards
 * compatibility when we switch to another tooltip library instead of
 * Bootstrap tooltips.
 *
 * If you need to pass multiple children, surround them with another element,
 * such as a `<span>` or `<div>`.
 *
 * **Note:** this component will overwrite the `title` attribute of the first
 * child you pass to it, as this is how the current tooltip system works in
 * Flarum. This shouldn't be an issue if you're using this component correctly.
 *
 * @example <caption>Basic usage</caption>
 *          <Tooltip text="You wish!">
 *            <Button>
 *              Click for free money!
 *            </Button>
 *          </Tooltip>
 *
 * @example <caption>Use of `position` and `showOnFocus` attrs</caption>
 *          <Tooltip text="Woah! That's cool!" position="bottom" showOnFocus>
 *            <span>3 replies</span>
 *          </Tooltip>
 *
 * @example <caption>Incorrect usage</caption>
 *          // This is wrong! Surround the children with a <span> or similar.
 *          <Tooltip text="This won't work">
 *            Click
 *            <a href="/">here</a>
 *          </Tooltip>
 */
export default class Tooltip extends Component<TooltipAttrs> {
    private firstChild;
    private childDomNode;
    private oldText;
    private oldVisibility;
    private shouldRecreateTooltip;
    private shouldChangeTooltipVisibility;
    view(vnode: Mithril.Vnode<TooltipAttrs, this>): Mithril.ChildArray;
    oncreate(vnode: Mithril.VnodeDOM<TooltipAttrs, this>): void;
    onupdate(vnode: Mithril.VnodeDOM<TooltipAttrs, this>): void;
    private recreateTooltip;
    private updateVisibility;
    private createTooltip;
    private getRealText;
    /**
     * Checks if the tooltip DOM node has changed.
     *
     * If it has, it updates `this.childDomNode` to the new node, and sets
     * `shouldRecreateTooltip` to `true`.
     */
    private checkDomNodeChanged;
}
