type BootstrapTooltipPlacement = 'top' | 'bottom' | 'left' | 'right';

// This is a backwards compatibility layer to provide support for $.tooltip()
export default function jqueryTooltipCompatLayer(element, options) {
  const tooltipText = this.attr('title');

  this.attr('data-tooltip', '');
  this.attr('aria-label', tooltipText);
  this.attr('title', null);

  const placement: BootstrapTooltipPlacement | undefined | null = options && options.placement;

  if (placement && placement !== 'top') {
    // Placement manually specified
    this.attr('data-tooltip-position', placement);
  }
}
