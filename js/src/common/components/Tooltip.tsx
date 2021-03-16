import Component, { ComponentAttrs } from '../Component';
import type Mithril from 'mithril';

export interface TooltipAttrs extends ComponentAttrs {
  /** Tooltip text */
  text: string;
  /** If inline, uses a `<span>` container, else uses a `<div>`. Default: `false`. */
  inline?: boolean;
  /** Manually show tooltip. `false` will show based on cursor events. Default: `false`. */
  tooltipVisible?: boolean;
  /** Whether to show on focus. Default: `true`. */
  showOnFocus?: boolean;
}

/**
 * The `Tooltip` component is used to create a tooltip for an element which
 * already has pseudo-elements as part of its styles, or text vnodes.
 *
 * If your element does not use pseudo-elements on its first direct child,
 * instead use the attributes `data-tooltip` and `aria-label="tooltip text"`
 * wherever possible.
 *
 * @example <caption>Correct use of Tooltip component</caption>
 * // div has ::before or ::after
 * <Tooltip text="Hello flarumites!">
 *   <div />
 * </Tooltip>
 *
 * @example <caption>INCORRECT use of Tooltip component</caption>
 * // Do not use this!
 * <Tooltip text="This is wrong!">
 *   <span>0 replies</span>
 * </Tooltip>
 *
 * // Use this instead
 * <span data-tooltip aria-label="This is correct!" />
 */
export default class Tooltip extends Component<TooltipAttrs> {
  view(vnode: Mithril.Vnode<TooltipAttrs, this>) {
    const { children } = vnode;

    const { text, inline, tooltipVisible, showOnFocus, ...attrs } = this.attrs;

    attrs['data-tooltip'] = true;
    attrs['aria-label'] = text;

    if (tooltipVisible) attrs['data-tooltip-visible'] = true;
    if (!showOnFocus) attrs['data-tooltip-nofocus'] = true;

    if (inline) {
      return <span {...attrs}>{children}</span>;
    }

    return <div {...attrs}>{children}</div>;
  }
}
