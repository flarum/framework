import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import Dropdown from '../../../../src/common/components/Dropdown';
import SelectDropdown from '../../../../src/common/components/SelectDropdown';
import Button from '../../../../src/common/components/Button';
import m from 'mithril';
import mq from 'mithril-query';
import { jest } from '@jest/globals';
import app from '../../../../src/forum/app';

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

  it('puts the visible label first in the accessible name', () => {
    const dropdown = mq(m(Dropdown, { label: 'Latest', accessibleToggleLabel: 'Change sorting' }, [m(Button, 'one')]));

    expect(dropdown).toHaveElementAttr('button.Dropdown-toggle', 'aria-label', 'Latest, Change sorting');
  });

  it('uses the accessible label alone when there is no visible label', () => {
    const dropdown = mq(m(Dropdown, { icon: 'fas fa-cog', accessibleToggleLabel: 'Change sorting' }, [m(Button, 'one')]));

    expect(dropdown).toHaveElementAttr('button.Dropdown-toggle', 'aria-label', 'Change sorting');
  });

  it('does not repeat a visible label the accessible label already contains', () => {
    const dropdown = mq(m(Dropdown, { label: 'Sorting', accessibleToggleLabel: 'Change sorting' }, [m(Button, 'one')]));

    expect(dropdown).toHaveElementAttr('button.Dropdown-toggle', 'aria-label', 'Change sorting');
  });

  it('names a select dropdown after its active option', () => {
    const dropdown = mq(
      m(SelectDropdown, { defaultLabel: 'All Discussions', accessibleToggleLabel: 'Toggle navigation dropdown menu' }, [
        m(Button, { active: false }, 'Following'),
        m(Button, { active: true }, 'Tags'),
      ])
    );

    expect(dropdown).toHaveElementAttr('button.Dropdown-toggle', 'aria-label', 'Tags, Toggle navigation dropdown menu');
  });

  it('names a select dropdown after its default label when no option is active', () => {
    const dropdown = mq(
      m(SelectDropdown, { defaultLabel: 'All Discussions', accessibleToggleLabel: 'Toggle navigation dropdown menu' }, [
        m(Button, { active: false }, 'Following'),
      ])
    );

    expect(dropdown).toHaveElementAttr('button.Dropdown-toggle', 'aria-label', 'All Discussions, Toggle navigation dropdown menu');
  });

  it('accepts a translated accessible label, which the translator returns as an array', () => {
    app.translator.addTranslations({ 'core.forum.index.toggle_sidenav_dropdown_accessible_label': 'Toggle navigation dropdown menu' });

    const label = app.translator.trans('core.forum.index.toggle_sidenav_dropdown_accessible_label');
    expect(Array.isArray(label)).toBe(true);

    const dropdown = mq(
      m(SelectDropdown, { defaultLabel: 'All Discussions', accessibleToggleLabel: label as any }, [m(Button, { active: true }, 'Tags')])
    );

    expect(dropdown).toHaveElementAttr('button.Dropdown-toggle', 'aria-label', 'Tags, Toggle navigation dropdown menu');
  });
});
