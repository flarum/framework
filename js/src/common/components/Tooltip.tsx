import Component, { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';
import classList from '../utils/classList';

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
 * @example <caption>Correct use of Tooltip component</caption>
 *          <Tooltip text="You wish!">
 *            <Button>
 *              Click for free money!
 *            </Button>
 *          </Tooltip>
 *
 * @example <caption>Correct use of Tooltip component</caption>
 *          <Tooltip text="Replies from xxx, yyy, zzz and more.">
 *            <div>3 replies</div>
 *          </Tooltip>
 */
export default class Tooltip extends Component<TooltipAttrs> {
  view(vnode: Mithril.Vnode<TooltipAttrs, this>) {
    const { children } = vnode;

    const {
      text,
      inline,
      tooltipVisible,
      showOnFocus = true,
      position = 'top',
      // This will have no effect when switching to CSS tooltips
      html = false,
      ...attrs
    } = this.attrs;

    attrs['aria-label'] = text;

    // https://getbootstrap.com/docs/3.3/javascript/#tooltips-options
    this.$.tooltip({
      html,
      placement: position,
      title: text,
      // Fancy "hack" to assemble the trigger string
      trigger: classList('hover', [showOnFocus && 'focus']),
    });

    if (inline) {
      return <span {...attrs}>{children}</span>;
    }

    return <div {...attrs}>{children}</div>;
  }
}
