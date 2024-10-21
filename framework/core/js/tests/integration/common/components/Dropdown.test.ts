import bootstrapForum from '@flarum/jest-config/src/boostrap/forum';
import Dropdown from '../../../../src/common/components/Dropdown';
import Button from '../../../../src/common/components/Button';
import m from 'mithril';
import mq from 'mithril-query';
import { jest } from '@jest/globals';

beforeAll(() => bootstrapForum());

describe('Dropdown displays as expected', () => {
  it('renders', () => {
    const dropdown = mq(
      m(
        Dropdown,
        {
          label: 'click me!',
          icon: 'fas fa-cog',
          tooltip: 'tooltip!',
          caretIcon: 'fas fa-caret-down',
          buttonClassName: 'buttonClassName',
          accessibleToggleLabel: 'toggle',
          menuClassName: 'menuClassName',
          onshow: jest.fn(),
          onhide: jest.fn(),
        },
        [m(Button, { onclick: jest.fn() }, 'button 1'), m(Button, { onclick: jest.fn() }, 'button 2')]
      )
    );
    expect(dropdown).toContainRaw('click me!');
  });
});
