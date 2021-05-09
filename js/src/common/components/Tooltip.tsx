import Component, { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
import classList from '../utils/classList';
import { TooltipCreationOptions } from '../../../@types/tooltips';
import extractText from '../utils/extractText';

export interface TooltipAttrs extends ComponentAttrs {
  /**
   * Tooltip textual content.
   *
   * String arrays, like those provided by the translator, will be flattened
   * into strings.
   */
  text: string | string[];
  /**
   * Defines the type of container to use. Chosen option defines the `display`
   * property of the container element in CSS.
   *
   * Default: `'block'`.
   */
  containerType?: 'block' | 'inline' | 'inline-block';
  /**
   * Manually show tooltip. `false` will show based on cursor events.
   *
   * Default: `false`.
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
   * be used wherever possible, and will not work when we migrate to CSS-only
   * tooltips.
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
   * **Warning:** this option will be removed when we switch to CSS-only
   * tooltips.
   *
   * Default: `0`.
   *
   * @deprecated
   */
  delay?: number;
}

/**
 * The `Tooltip` component is used to create a tooltip for an element. It
 * surrounds it with a div (or span) which has the required tooltip setup
 * applied.
 *
 * You should use this for any tooltips you create to allow for backwards
 * compatibility when we switch to pure CSS tooltips instead of Bootstrap
 * tooltips.
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
 *            <div>3 replies</div>
 *          </Tooltip>
 */
export default class Tooltip extends Component<TooltipAttrs> {
  private oldText: string = '';
  private oldVisibility: boolean | undefined;

  private shouldRecreateTooltip: boolean = false;
  private shouldChangeTooltipVisibility: boolean = false;

  view(vnode) {
    const { children } = vnode;

    if (this.attrs.title) {
      console.warn(
        '`title` attribute was passed to Tooltip component. Was this intentional? Tooltip content should be passed to the `text` attr instead.'
      );
    }

    // We remove these to get the remaining attrs to pass to the DOM element
    const {
      text,
      containerType = 'block',
      tooltipVisible,
      showOnFocus = true,
      position = 'top',
      html = false,
      delay = 0,
      className,
      class: classes,
      ...attrs
    } = this.attrs;

    const realText = Array.isArray(text) ? extractText(text) : text;

    // We need to recreate the tooltip if the text has changed
    if (realText !== this.oldText) {
      this.oldText = realText;
      this.shouldRecreateTooltip = true;
    }

    if (tooltipVisible !== this.oldVisibility) {
      this.oldVisibility = this.attrs.tooltipVisible;
      this.shouldChangeTooltipVisibility = true;
    }

    return (
      <div title={realText} className={classList('tooltip-container', `tooltip-container--${containerType}`, className, classes)} {...attrs}>
        {children}
      </div>
    );
  }

  oncreate(vnode: Mithril.VnodeDOM<TooltipAttrs, this>) {
    super.oncreate(vnode);

    this.recreateTooltip();
  }

  onupdate(vnode: Mithril.VnodeDOM<TooltipAttrs, this>) {
    super.onupdate(vnode);

    this.recreateTooltip();
  }

  private recreateTooltip() {
    if (this.shouldRecreateTooltip) {
      this.$().tooltip(
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
    if (this.attrs.tooltipVisible === true) {
      this.$().tooltip(
        'show',
        // @ts-expect-error We don't want this arg to be part of the public API. It only exists to prevent deprecation warnings when using `$.tooltip` in this component.
        'DANGEROUS_tooltip_jquery_fn_deprecation_exempt'
      );
    } else if (this.attrs.tooltipVisible === false) {
      this.$().tooltip(
        'hide',
        // @ts-expect-error We don't want this arg to be part of the public API. It only exists to prevent deprecation warnings when using `$.tooltip` in this component.
        'DANGEROUS_tooltip_jquery_fn_deprecation_exempt'
      );
    }
  }

  private createTooltip() {
    const {
      showOnFocus = true,
      position = 'top',
      delay,
      // This will have no effect when switching to CSS tooltips
      html = false,
      tooltipVisible,
    } = this.attrs;

    const trigger = (typeof tooltipVisible === 'boolean'
      ? 'manual'
      : classList('hover', [showOnFocus && 'focus'])) as TooltipCreationOptions['trigger'];

    // https://getbootstrap.com/docs/3.3/javascript/#tooltips-options
    this.$().tooltip(
      {
        html,
        delay,
        placement: position,
        // Fancy "hack" to assemble the trigger string
        trigger,
      },
      // @ts-expect-error We don't want this arg to be part of the public API. It only exists to prevent deprecation warnings when using `$.tooltip` in this component.
      'DANGEROUS_tooltip_jquery_fn_deprecation_exempt'
    );
  }
}
