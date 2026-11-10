import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import Footer from '../../../../src/forum/components/Footer';
import ItemList from '../../../../src/common/utils/ItemList';
import { extend } from '../../../../src/common/extend';
import m from 'mithril';
import mq from 'mithril-query';

beforeAll(() => bootstrapForum());

describe('Footer', () => {
  const original = Footer.prototype.items;

  afterEach(() => {
    Footer.prototype.items = original;
  });

  it('renders nothing when no extension has added an item', () => {
    const rendered = mq(m(Footer));

    expect(rendered).not.toHaveElement('.Footer-container');
    // The mount point sits on every page, so an empty footer must not
    // contribute a container of its own.
    expect(rendered).not.toHaveElement('.container');
  });

  it('returns an empty ItemList by default', () => {
    const items = Footer.prototype.items.call(null);

    expect(items).toBeInstanceOf(ItemList);
    expect(items.toArray()).toHaveLength(0);
  });

  it('renders the items an extension adds, inside a container', () => {
    extend(Footer.prototype, 'items', function (items: ItemList<any>) {
      items.add('legal', m('a', { className: 'TestFooterLink', href: '/legal' }, 'Terms'));
    });

    const rendered = mq(m(Footer));

    expect(rendered).toHaveElement('.Footer-container.container');
    expect(rendered).toHaveElement('.Footer-container .TestFooterLink');
    expect(rendered).toContainRaw('Terms');
  });

  it('renders several items in priority order', () => {
    extend(Footer.prototype, 'items', function (items: ItemList<any>) {
      items.add('second', m('span', { className: 'TestFooterItem' }, 'Second'), 10);
      items.add('first', m('span', { className: 'TestFooterItem' }, 'First'), 20);
    });

    const rendered = mq(m(Footer));

    expect(rendered.find('.TestFooterItem')).toHaveLength(2);
    // Read off the rendered DOM rather than the ItemList, so that a view
    // which rendered the items in some other order would be caught.
    expect(Array.from(rendered.rootEl.querySelectorAll('.TestFooterItem')).map((el: any) => el.textContent)).toEqual(['First', 'Second']);
  });
});
