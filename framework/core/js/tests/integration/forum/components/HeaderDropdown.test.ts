import { jest } from '@jest/globals';
import m from 'mithril';
import mq from 'mithril-query';
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
