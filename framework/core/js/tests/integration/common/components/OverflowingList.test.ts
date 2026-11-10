import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import m from 'mithril';
import mq from 'mithril-query';
import OverflowingList from '../../../../src/common/components/OverflowingList';
import Button from '../../../../src/common/components/Button';
import ItemList from '../../../../src/common/utils/ItemList';

beforeAll(() => bootstrapForum());

/**
 * jsdom has no layout engine: every element measures zero and nothing ever
 * overflows, so the collapsing itself cannot be exercised here — that
 * arithmetic is covered directly in the unit tests for `countItemsThatFit`.
 * What these check is the markup either side of that decision: what renders
 * when nothing is collapsed, and what the overflow menu looks like when
 * something is.
 */
function items(...labels: string[]) {
  const list = new ItemList();

  labels.forEach((label, i) => list.add(label, m(Button, { className: 'TestItem' }, label), 100 - i));

  return list.toArray();
}

describe('OverflowingList renders its items', () => {
  it('renders every item when nothing has been collapsed', () => {
    const rendered = mq(m(OverflowingList, { items: items('One', 'Two', 'Three') }));

    expect(rendered).toContainRaw('One');
    expect(rendered).toContainRaw('Two');
    expect(rendered).toContainRaw('Three');
  });

  it('shows no overflow toggle when everything fits', () => {
    // Nothing measures anything in jsdom, so nothing overflows — which is the
    // state being asserted.
    const rendered = mq(m(OverflowingList, { items: items('One', 'Two') }));

    expect(rendered).not.toHaveElement('.OverflowingList-toggle');
  });

  it('renders as a list', () => {
    expect(mq(m(OverflowingList, { items: items('One') }))).toHaveElement('ul.OverflowingList');
  });

  it('keeps the class name it is given alongside its own', () => {
    const rendered = mq(m(OverflowingList, { items: items('One'), className: 'Header-controls' }));

    expect(rendered).toHaveElement('ul.OverflowingList.Header-controls');
  });

  it('renders nothing but an empty list when given no items', () => {
    const rendered = mq(m(OverflowingList, { items: [] }));

    expect(rendered).toHaveElement('ul.OverflowingList');
    expect(rendered).not.toHaveElement('.OverflowingList-toggle');
  });

  it('survives being given no items attribute at all', () => {
    expect(() => mq(m(OverflowingList, {} as any))).not.toThrow();
  });
});

describe('OverflowingList overflow menu', () => {
  /**
   * Force a collapsed state. `visibleCount` is what the measuring pass would
   * normally set; setting it directly is the only way to see the collapsed
   * markup in an environment with no layout.
   */
  function collapsedTo(visible: number, ...labels: string[]) {
    class Collapsed extends OverflowingList {
      protected visibleCount = visible;

      // The measuring pass would immediately undo the count set above.
      protected recalculate() {}
    }

    return mq(m(Collapsed, { items: items(...labels) }));
  }

  it('shows a toggle once something has been collapsed', () => {
    expect(collapsedTo(1, 'One', 'Two', 'Three')).toHaveElement('.OverflowingList-toggle');
  });

  it('keeps the items that fit on the row', () => {
    expect(collapsedTo(1, 'One', 'Two', 'Three')).toContainRaw('One');
  });

  it('moves the rest into the menu rather than dropping them', () => {
    // The whole point of collapsing rather than hiding: nothing disappears.
    const rendered = collapsedTo(1, 'One', 'Two', 'Three');

    expect(rendered).toContainRaw('Two');
    expect(rendered).toContainRaw('Three');
  });

  it('gives the toggle an accessible name', () => {
    expect(collapsedTo(1, 'One', 'Two')).toHaveElement('.OverflowingList-dropdown [aria-label]');
  });

  it('shows no caret beside the ellipsis', () => {
    // The ellipsis already says there is more; a dropdown adds a caret by
    // default, which doubled up on that.
    expect(collapsedTo(1, 'One', 'Two')).not.toHaveElement('.OverflowingList-toggle .Button-caret');
  });

  it('collapses everything when nothing fits', () => {
    const rendered = collapsedTo(0, 'One', 'Two');

    expect(rendered).toHaveElement('.OverflowingList-toggle');
    expect(rendered).toContainRaw('One');
    expect(rendered).toContainRaw('Two');
  });
});
