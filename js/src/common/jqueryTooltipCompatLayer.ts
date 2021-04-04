type BootstrapTooltipPlacement = 'top' | 'bottom' | 'left' | 'right';

interface TooltipOptions {
  placement: BootstrapTooltipPlacement;
}

/**
 * This is a backwards compatibility layer to provide support for `$.tooltip()`, since it
 * is used extensively across multiple core and extension files.
 *
 * **Do not use this in new code. Instead add `data-tooltip` and `aria-label="text"`
 * attributes to create a tooltip, or use the `<Tooltip>` component from core if the
 * element already has styles on its `::before` or `::after`.**
 *
 * @deprecated Backwards compatibility layer for Bootstrap tooltips.
 * @param options Tooltip options
 */
export default function jqueryTooltipCompatLayer(this: JQuery, options: TooltipOptions): JQuery {
  const tooltipText = this.attr('title') || this.attr('aria-label');

  if (typeof tooltipText === 'undefined' || tooltipText === '') {
    console.warn(`Called $.tooltip() on element without a title or aria-label attribute.`, this.get(0));
    return this;
  }

  this.attr('data-tooltip', '');
  this.attr('aria-label', tooltipText);
  this.attr('title', null);

  const placement: BootstrapTooltipPlacement | undefined | null = options && options.placement;

  if (placement && placement !== 'top') {
    // Placement manually specified
    this.attr('data-tooltip-position', placement);
  }

  return this;
}
