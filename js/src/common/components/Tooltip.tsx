import Component, { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
import classList from '../utils/classList';
import { TooltipCreationOptions } from '../../../@types/tooltips';

export interface TooltipAttrs extends ComponentAttrs {
  /**
   * Tooltip textual content.
   */
  text: string;
  /**
   * If inline, uses a `<span>` container, else uses a `<div>`.
   *
   * Default: `false`.
   */
  inline?: boolean;
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
   */
  position?: 'top' | 'bottom' | 'left' | 'right';
  /**
   * Whether HTML content is allowed in the tooltip.
   *
   * **Warning:** this is a possible XSS attack vector. This option shouldn't
   * be used wherever possible, and will not work when we migrate to CSS-only
   * tooltips.
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
  view(vnode) {
    const { children } = vnode;

    // We remove these to get the remaining attrs to pass to the DOM element
    const { text, inline, tooltipVisible, showOnFocus, position, html, delay, ...attrs } = this.attrs;

    if (inline) {
      return <span {...attrs}>{children}</span>;
    }

    return <div {...attrs}>{children}</div>;
  }

  oncreate(vnode: Mithril.VnodeDOM<TooltipAttrs, this>) {
    super.oncreate(vnode);

    this.createTooltip();
  }

  onupdate(vnode: Mithril.VnodeDOM<TooltipAttrs, this>) {
    this.createTooltip();
  }

  private createTooltip() {
    const {
      text,
      inline,
      tooltipVisible,
      showOnFocus = true,
      position = 'top',
      delay,
      // This will have no effect when switching to CSS tooltips
      html = false,
      ...attrs
    } = this.attrs;

    this.attrs['aria-label'] = text;

    // https://getbootstrap.com/docs/3.3/javascript/#tooltips-options
    this.$().tooltip({
      html,
      delay,
      placement: position,
      title: text,
      // Fancy "hack" to assemble the trigger string
      trigger: classList('hover', [showOnFocus && 'focus']) as TooltipCreationOptions['trigger'],
    });
  }
}
