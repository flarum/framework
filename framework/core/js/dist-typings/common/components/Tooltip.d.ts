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
    /** Accessible name for the child when it differs from the tooltip text. */
    ariaLabel?: string;
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
     * Appends the tooltip to a specific element. Useful when the tooltip trigger
     * is inside a container with `overflow: hidden` or `overflow: scroll` that
     * would otherwise clip the tooltip. Accepts a CSS selector string or DOM
     * element — `'body'` is the most common value.
     *
     * Default: `false` (appended adjacent to the trigger element).
     */
    container?: string | HTMLElement | false;
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
    private oldAriaLabel?;
    private oldVisibility;
    private oldOptions;
    private shouldRecreateTooltip;
    private shouldUpdateText;
    private shouldChangeTooltipVisibility;
    private scrollBound;
    private touchMoveNode;
    private focusedAt;
    view(vnode: Mithril.Vnode<TooltipAttrs, this>): Mithril.ChildArray;
    oncreate(vnode: Mithril.VnodeDOM<TooltipAttrs, this>): void;
    onupdate(vnode: Mithril.VnodeDOM<TooltipAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<TooltipAttrs, this>): void;
    /**
     * Hides the tooltip when its element is activated, or when a touch gesture
     * starts to scroll or is taken over by the browser.
     *
     * A touch screen turns a tap into a hover and a focus as well as a click, so
     * Bootstrap would otherwise keep the tooltip up until the pointer and focus
     * both left, leaving it over the modal or the next control that was tapped.
     * A long press, or a tap that turns into a scroll, never ends in a click, so
     * those gestures are handled here too.
     */
    private hideOnActivate;
    /**
     * Hides a tooltip that was appended to a container on scroll.
     *
     * Bootstrap 3 positions such a tooltip against the page when it opens, and
     * does not move it again. For a trigger in the fixed header that leaves the
     * tooltip behind over the content as the page scrolls; hiding it is better
     * than pointing at the wrong control. Tooltips left beside their trigger move
     * with it and need no handling.
     *
     * A scroll that arrives just after the trigger is focused is the browser
     * bringing it into view, not the page moving, so it leaves the tooltip up.
     * Any later scroll, whether from Page Down, an arrow key, a scrollbar drag
     * or a wheel, hides it.
     */
    private onScroll;
    private hideIfShown;
    /**
     * Listens on the current child element, dropping the listeners from the
     * previous one when Mithril replaces it.
     */
    private bindChildEvents;
    /**
     * Binds the touch gesture check directly and passively. jQuery adds a
     * non-passive listener, which would make the first scroll after touching a
     * control wait on it.
     */
    private bindTouchMove;
    private unbindTouchMove;
    /**
     * Watches for scrolling while a container tooltip is showing.
     *
     * The time of the focus is recorded too, so that the scroll that brings the
     * triggering control into view when it gets focus does not hide the tooltip
     * that the focus just showed.
     */
    private bindScroll;
    private unbindScroll;
    private markFocused;
    /**
     * Keyboard scrolling moves the page with no pointer activity at all, so the
     * scroll that follows a page key must not be taken for the browser bringing
     * the focused control into view. Space counts here only when it scrolls:
     * activating the focused control with it does not.
     */
    private onScrollKey;
    private hideTooltip;
    private recreateTooltip;
    private updateText;
    private updateVisibility;
    private createTooltip;
    /**
     * Destroys the Bootstrap tooltip at once, returning whether it was shown.
     */
    private destroyTooltip;
    /**
     * Shows a rebuilt hover/focus tooltip again while the element is hovered
     * or focused with a trigger the tooltip uses, so it hides as usual when
     * the pointer or focus leaves.
     */
    private showIfActive;
    private getTrigger;
    private setAriaLabel;
    private getRealText;
    /**
     * Checks if the tooltip DOM node has changed.
     *
     * If it has, it updates `this.childDomNode` to the new node, and sets
     * `shouldRecreateTooltip` to `true`.
     */
    private checkDomNodeChanged;
}
