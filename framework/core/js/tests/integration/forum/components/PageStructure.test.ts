import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import m from 'mithril';
import mq from 'mithril-query';
import PageStructure from '../../../../src/forum/components/PageStructure';

beforeAll(() => bootstrapForum());

describe('PageStructure', () => {
  it('renders its content when a sidebar is provided', () => {
    const page = mq(m(PageStructure, { className: 'TestPage', sidebar: () => m('div', 'Sidebar') }, 'Content'));

    expect(page.contains('Content')).toBe(true);
    expect(page.contains('Sidebar')).toBe(true);
  });

  it('renders its content when the sidebar attribute is omitted', () => {
    const page = mq(m(PageStructure, { className: 'TestPage' }, 'Content'));

    expect(page.contains('Content')).toBe(true);
  });

  it('renders its content when no attributes are given', () => {
    // @ts-expect-error className is typed as required, but the docs list it as optional
    const page = mq(m(PageStructure, {}, 'Content'));

    expect(page.contains('Content')).toBe(true);
  });
});
