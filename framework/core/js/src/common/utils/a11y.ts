/**
 * Fix a11y skip links by manually focusing on the href target element.
 * This prevents unwanted/unexpected reloads of the page.
 */
export function prepareSkipLinks() {
  document.querySelectorAll('.sr-only-focusable-custom:not([data-prepared])').forEach((el) => {
    el.addEventListener('click', function (e) {
      e.preventDefault();
      const target = el.getAttribute('href')!;
      const $target = document.querySelector(target) as HTMLElement;

      if ($target) {
        $target.setAttribute('tabindex', '-1');
        $target.focus();
        $target.removeAttribute('tabindex');

        $target.dataset.prepared = 'true';
      }
    });
  });
}
