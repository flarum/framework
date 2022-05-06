import Component from '../Component';
import type Mithril from 'mithril';
import classList from '../utils/classList';
import extractText from '../utils/extractText';

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
  private firstChild: Mithril.Vnode<any, any> | null = null;
  private childDomNode: HTMLElement | null = null;

  private oldText: string = '';
  private oldVisibility: boolean | undefined;

  private shouldRecreateTooltip: boolean = false;
  private shouldChangeTooltipVisibility: boolean = false;

  view(vnode: Mithril.Vnode<TooltipAttrs, this>) {
    /**
     * We know this will be a ChildArray and not a primitive as this
     * vnode is a component, not a text or trusted HTML vnode.
     */
    const children = vnode.children as Mithril.ChildArray | undefined;

    // We remove these to get the remaining attrs to pass to the DOM element
    const { text, tooltipVisible, showOnFocus = true, position = 'top', ignoreTitleWarning = false, html = false, delay = 0, ...attrs } = this.attrs;

    if ((this.attrs as any).title && !ignoreTitleWarning) {
      console.warn(
        '`title` attribute was passed to Tooltip component. Was this intentional? Tooltip content should be passed to the `text` attr instead.'
      );
    }

    const realText = this.getRealText();

    // We need to recreate the tooltip if the text has changed
    if (realText !== this.oldText) {
      this.oldText = realText;
      this.shouldRecreateTooltip = true;
    }

    if (tooltipVisible !== this.oldVisibility) {
      this.oldVisibility = this.attrs.tooltipVisible;
      this.shouldChangeTooltipVisibility = true;
    }

    // We'll try our best to detect any issues created by devs before they cause any weird effects.
    // Throwing an error will prevent the forum rendering, but will be better at alerting devs to
    // an issue.

    if (typeof children === 'undefined') {
      throw new Error(
        `Tooltip component was provided with no direct child DOM element. Tooltips must contain a single direct DOM node to attach to.`
      );
    }

    if (children.length !== 1) {
      throw new Error(
        `Tooltip component was either passed more than one or no child node.\n\nPlease wrap multiple children in another element, such as a <div> or <span>.`
      );
    }

    const firstChild = children[0];

    if (typeof firstChild !== 'object' || Array.isArray(firstChild) || firstChild === null) {
      throw new Error(
        `Tooltip component was provided with no direct child DOM element. Tooltips must contain a single direct DOM node to attach to.`
      );
    }

    if (typeof firstChild.tag === 'string' && ['#', '[', '<'].includes(firstChild.tag)) {
      throw new Error(
        `Tooltip component with provided with a vnode with tag "${firstChild.tag}". This is not a DOM element, so is not a valid child element. Please wrap this vnode in another element, such as a <div> or <span>.`
      );
    }

    this.firstChild = firstChild;

    return children;
  }

  oncreate(vnode: Mithril.VnodeDOM<TooltipAttrs, this>) {
    super.oncreate(vnode);

    this.checkDomNodeChanged();
    this.recreateTooltip();
  }

  onupdate(vnode: Mithril.VnodeDOM<TooltipAttrs, this>) {
    super.onupdate(vnode);

    this.checkDomNodeChanged();
    this.recreateTooltip();
  }

  private recreateTooltip() {
    if (this.shouldRecreateTooltip && this.childDomNode !== null) {
      $(this.childDomNode).tooltip(
        'destroy',
        // @ts-expect-error We don't want this arg to be part of the public API. It only exists to prevent deprecation warnings when using `$.tooltip` in this component.
        'DANGEROUS_tooltip_jquery_fn_deprecation_exempt'
      );
      this.createTooltip();
      this.shouldRecreateTooltip = false;
    }

    if (this.shouldChangeTooltipVisibility) {
      this.shouldChangeTooltipVisibility = false;
      this.updateVisibility();
    }
  }

  private updateVisibility() {
    if (this.childDomNode === null) return;

    if (this.attrs.tooltipVisible === true) {
      $(this.childDomNode).tooltip(
        'show',
        // @ts-expect-error We don't want this arg to be part of the public API. It only exists to prevent deprecation warnings when using `$.tooltip` in this component.
        'DANGEROUS_tooltip_jquery_fn_deprecation_exempt'
      );
    } else if (this.attrs.tooltipVisible === false) {
      $(this.childDomNode).tooltip(
        'hide',
        // @ts-expect-error We don't want this arg to be part of the public API. It only exists to prevent deprecation warnings when using `$.tooltip` in this component.
        'DANGEROUS_tooltip_jquery_fn_deprecation_exempt'
      );
    }
  }

  private createTooltip() {
    if (this.childDomNode === null) return;

    const {
      showOnFocus = true,
      position = 'top',
      delay,
      // This will have no effect when switching to CSS tooltips
      html = false,
      tooltipVisible,
      text,
    } = this.attrs;

    // Fancy "hack" to assemble the trigger string
    const trigger = typeof tooltipVisible === 'boolean' ? 'manual' : classList('hover', [showOnFocus && 'focus']);

    const realText = this.getRealText();
    this.childDomNode.setAttribute('title', realText);
    this.childDomNode.setAttribute('aria-label', realText);

    // https://getbootstrap.com/docs/3.3/javascript/#tooltips-options
    $(this.childDomNode).tooltip(
      {
        html,
        delay,
        placement: position,
        trigger,
      },
      // @ts-expect-error We don't want this arg to be part of the public API. It only exists to prevent deprecation warnings when using `$.tooltip` in this component.
      'DANGEROUS_tooltip_jquery_fn_deprecation_exempt'
    );
  }

  private getRealText(): string {
    const { text } = this.attrs;

    return Array.isArray(text) ? extractText(text) : text;
  }

  /**
   * Checks if the tooltip DOM node has changed.
   *
   * If it has, it updates `this.childDomNode` to the new node, and sets
   * `shouldRecreateTooltip` to `true`.
   */
  private checkDomNodeChanged() {
    const domNode = (this.firstChild as Mithril.VnodeDOM<any, any>).dom as HTMLElement;

    if (domNode && !domNode.isSameNode(this.childDomNode)) {
      this.childDomNode = domNode;
      this.shouldRecreateTooltip = true;
    }
  }
}
