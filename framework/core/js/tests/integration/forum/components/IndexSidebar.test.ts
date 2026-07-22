import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import IndexSidebar from '../../../../src/forum/components/IndexSidebar';
import { app } from '../../../../src/forum';
import mq from 'mithril-query';
import m from 'mithril';

beforeAll(() => bootstrapForum());

const titleControlLabel = (sidebar: any): string | undefined =>
  sidebar.rootEl.querySelector('.App-titleControl > .Dropdown-toggle .Button-labelText')?.textContent;

const navItemLabel = (sidebar: any, item: string): string | undefined =>
  sidebar.rootEl.querySelector(`.Dropdown-menu .item-${item} .Button-labelText`)?.textContent;

describe('IndexSidebar', () => {
  beforeAll(() => app.boot());

  afterEach(() => app.current.set('titleControlLabel', undefined));

  test('renders', () => {
    const sidebar = mq(IndexSidebar, {});

    expect(sidebar).toHaveElement('.App-titleControl');
  });

  test('labels the title control with the index link when no nav item is active', () => {
    const sidebar = mq(IndexSidebar, {});

    expect(titleControlLabel(sidebar)).toBeTruthy();
    expect(titleControlLabel(sidebar)).toBe(navItemLabel(sidebar, 'allDiscussions'));
  });

  test('labels the title control from app.current when a page provides a label', () => {
    app.current.set('titleControlLabel', 'Support');

    const sidebar = mq(IndexSidebar, {});

    expect(titleControlLabel(sidebar)).toBe('Support');
  });

  test('prefers an active nav item over the label from app.current', () => {
    class SidebarWithActiveItem extends IndexSidebar {
      navItems() {
        const items = super.navItems();

        items.add('active', m('button', { active: true }, 'Active item'), 200);

        return items;
      }
    }

    app.current.set('titleControlLabel', 'Support');

    const sidebar = mq(SidebarWithActiveItem, {});

    expect(titleControlLabel(sidebar)).toBe('Active item');
  });
});
