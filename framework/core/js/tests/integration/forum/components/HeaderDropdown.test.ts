import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import m from 'mithril';
import mq from 'mithril-query';
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

beforeAll(() => bootstrapForum());

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

  header.click('button.Dropdown-toggle');

  expect(load).toHaveBeenCalledTimes(1);
});
