import { jest } from '@jest/globals';
import m from 'mithril';
import mq from 'mithril-query';
import app from '../../../../src/forum/app';
// Imported before HeaderDropdown so the app's component registry is set up
// first, which avoids the circular import with NotificationsDropdown.
import { bsTooltip, removeTooltips, useRealBootstrapTooltips } from '../../common/tooltips';
import HeaderDropdown from '../../../../src/forum/components/HeaderDropdown';

class TestHeaderDropdown extends HeaderDropdown {
  getUnreadCount() {
    return 1;
  }

  getNewCount() {
    return 1;
  }

  getContent() {
    return null;
  }

  goToRoute() {}
}

beforeAll(useRealBootstrapTooltips);

afterEach(removeTooltips);

test('keeps the new indicator, accessible name, and click handler with a custom tooltip', () => {
  const load = jest.fn();
  const header = mq(
    m(TestHeaderDropdown, {
      label: 'Notifications',
      accessibleToggleLabel: 'View notifications',
      tooltip: 'Recent notifications',
      state: { load },
    })
  );

  expect(header).toHaveElement('button.Dropdown-toggle.new[aria-label="View notifications"]');

  // The tooltip describes the control, but the button keeps its own accessible
  // name rather than taking the tooltip text.
  const button = header.find('button.Dropdown-toggle')[0] as HTMLElement;
  expect(bsTooltip(button).getTitle()).toBe('Recent notifications');

  header.click('button.Dropdown-toggle');

  expect(load).toHaveBeenCalledTimes(1);
});

test('does not show a tooltip while the phone drawer is open', () => {
  const appElement = document.getElementById('app')!;
  const root = document.createElement('div');
  document.body.appendChild(root);

  appElement.classList.add('drawerOpen');
  m.mount(root, { view: () => m(TestHeaderDropdown, { label: 'Notifications', state: { load: () => {} } }) });

  // The drawer stacks the controls, and the label on this one is already
  // visible; a tooltip would cover the user menu row below it.
  const button = root.querySelector<HTMLElement>('button.Dropdown-toggle')!;
  $(button).trigger('mouseenter');
  expect($('.tooltip.in').length).toBe(0);

  // The control keeps its tooltip for the header, without a redraw.
  appElement.classList.remove('drawerOpen');
  $(button).trigger('mouseleave');
  $(button).trigger('mouseenter');
  expect($('.tooltip.in .tooltip-inner').text()).toBe('Notifications');

  m.mount(root, null);
  root.remove();
});
