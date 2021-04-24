import Application from '../Application';

/**
 * Set up tooltip watcher.
 *
 * This ensures that no tooltips are shown off-screen, using a `MutationObserver` which watches for new tooltips.
 *
 * @private
 */
export default function tooltipWatcherInitializer(this: typeof Application) {
  if (!(this instanceof Application)) {
    console.error(
      `Tooltip watcher initializer called with 'this' parameter which is not an instance of 'Application'.

Are you calling this util manually? This should only be used by Flarum's core and only once per session.`
    );
    return;
  }

  if (this.tooltipWatcher.observer instanceof MutationObserver) {
    console.warn(`Tooltip watcher has already been initialized. Not initializing again...`);
    return;
  }

  this.tooltipWatcher.observer = new MutationObserver((mutations, observer) => {
    mutations.forEach((mutation) => {
      // If it's not an HTMLElement, we can ignore it
      if (!(mutation.target instanceof HTMLElement)) return;

      if (mutation.type === 'attributes') attributeMutationHandler(mutation);
      else if (mutation.type === 'childList') childListMutationHandler(mutation);
    });
  });

  this.tooltipWatcher.observer.observe(document.documentElement, {
    // Watch entire <body> tree
    subtree: true,
    // Watch for addition/removal of nodes
    childList: true,
    // Watch for attribute modification
    attributes: true,
    // Only look for tooltip-related attribute modifications
    attributeFilter: ['data-tooltip', 'aria-label'],
  });
}

function attributeMutationHandler(mutation: MutationRecord) {
  const node = mutation.target as HTMLElement;

  // Only look for valid tooltip elements
  if (!node.hasAttribute('data-tooltip') || !node.hasAttribute('aria-label')) return;

  // If the modified attribute isn't a tooltip one, we don't care
  if (!['data-tooltip', 'aria-label'].includes(mutation.attributeName || '')) return;

  // From this point on, we know we have a new/modified tooltip! Woohoo!
  // console.log('mod tooltip', node);
  checkAndHandlePossibleOffScreenTooltip(node);
}

function childListMutationHandler(mutation: MutationRecord) {
  // const parentNode = mutation.target as HTMLElement;

  mutation.addedNodes.forEach((node) => {
    if (!(node instanceof HTMLElement)) return;

    // Only look for valid tooltip elements
    if (!node.hasAttribute('data-tooltip') || !node.hasAttribute('aria-label')) return;

    // From this point on, we know we have a new tooltip! Woohoo!
    // console.log('new tooltip', node);
    checkAndHandlePossibleOffScreenTooltip(node);
  });
}

/**
 * With the provided `node`, we check if the tooltip will appear off-screen.
 *
 * If it does, we apply an appropriate `transformX` to prevent this.
 */
function checkAndHandlePossibleOffScreenTooltip(node: HTMLElement) {
  // VERY WIP AND NOT WORKING CODE BELOW
  const rect = node.getClientRects()[0];
  const pseudoStyles = window.getComputedStyle(node, ':before');

  const { left: pseudoLeft, right: pseudoRight } = pseudoStyles;

  const leftOffScreen =
    parseInt(pseudoStyles.width) / 2 - (rect.left + window.pageXOffset - document.documentElement.clientLeft + parseInt(pseudoLeft));
  const rightOffScreen =
    parseInt(pseudoStyles.width) / 2 - (rect.right + window.pageXOffset - document.documentElement.clientLeft + parseInt(pseudoRight));

  // Tooltip is not off-screen
  if (leftOffScreen < 0 && rightOffScreen < 0) return;
  if (leftOffScreen && rightOffScreen) console.log(leftOffScreen, rightOffScreen, node);
}
