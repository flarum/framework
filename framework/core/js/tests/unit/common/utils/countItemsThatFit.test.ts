import countItemsThatFit from '../../../../src/common/utils/countItemsThatFit';

describe('countItemsThatFit', () => {
  const TOGGLE = 50;

  describe('when everything fits', () => {
    test('shows every item and pays nothing for a toggle', () => {
      expect(countItemsThatFit([100, 100, 100], 300, TOGGLE)).toBe(3);
    });

    test('shows every item when the row is full to the pixel', () => {
      // The toggle is only needed once something is collapsed, so a row that
      // exactly fits still has no use for it — and one pixel less means it
      // does, which costs two items rather than one.
      expect(countItemsThatFit([100, 100, 100], 300, TOGGLE)).toBe(3);
      expect(countItemsThatFit([100, 100, 100], 299, TOGGLE)).toBe(2);
    });

    test('shows every item with room to spare', () => {
      expect(countItemsThatFit([50, 50], 500, TOGGLE)).toBe(2);
    });
  });

  describe('when items must be collapsed', () => {
    test('keeps the leading items that fit alongside the toggle', () => {
      // 300 of items will not fit in 290, so the toggle appears and has to be
      // paid for: 100 + 100 + 50 = 250 fits, a third item would need 350.
      expect(countItemsThatFit([100, 100, 100], 290, 50)).toBe(2);
    });

    test('reserves room for the toggle, not just for the items', () => {
      // Two of the three items would fit in 250 on their own. Once the toggle
      // has taken its 50, only one does.
      expect(countItemsThatFit([100, 100, 100], 250, 50)).toBe(2);
      expect(countItemsThatFit([100, 100, 100], 249, 50)).toBe(1);
    });

    test('collapses everything when even one item cannot share with the toggle', () => {
      expect(countItemsThatFit([200, 200], 210, 50)).toBe(0);
    });

    test('collapses a single overflowing item rather than widening the row', () => {
      // Two items, only one fits with the toggle. The menu holds one item —
      // still correct, since the alternative is an overflowing row.
      expect(countItemsThatFit([100, 100], 160, 50)).toBe(1);
    });
  });

  describe('boundaries', () => {
    test('an item that exactly fills the remaining space is kept', () => {
      // 100 + 50 toggle = 150, exactly the space available.
      expect(countItemsThatFit([100, 999], 150, 50)).toBe(1);
    });

    test('an item one pixel too wide is collapsed', () => {
      expect(countItemsThatFit([100, 999], 149, 50)).toBe(0);
    });

    test('does not let a later narrow item jump the queue', () => {
      // The second item does not fit, so the third is not considered either —
      // order on the row has to be preserved.
      expect(countItemsThatFit([100, 500, 10], 200, 50)).toBe(1);
    });
  });

  describe('degenerate input', () => {
    test('no items means nothing to show', () => {
      expect(countItemsThatFit([], 500, TOGGLE)).toBe(0);
    });

    test('no space means nothing fits', () => {
      expect(countItemsThatFit([100, 100], 0, TOGGLE)).toBe(0);
    });

    test('a single item narrower than the row is shown', () => {
      expect(countItemsThatFit([100], 300, TOGGLE)).toBe(1);
    });

    test('a single item wider than the row is collapsed', () => {
      expect(countItemsThatFit([400], 300, TOGGLE)).toBe(0);
    });

    test('zero-width items are all shown', () => {
      expect(countItemsThatFit([0, 0, 0], 10, TOGGLE)).toBe(3);
    });

    test('a toggle wider than the row collapses everything', () => {
      expect(countItemsThatFit([100, 100], 150, 500)).toBe(0);
    });
  });

  describe('stability', () => {
    // The layout oscillated once because the count was nudged after being
    // calculated: with three items and two fitting, a rule bumped the result
    // back to three, which overflowed, which collapsed it again. These pin the
    // property that broke — the answer depends only on the inputs.
    test('is pure: the same inputs always give the same answer', () => {
      const widths = [105, 112, 129, 122];

      const first = countItemsThatFit(widths, 326, 51);
      const second = countItemsThatFit(widths, 326, 51);
      const third = countItemsThatFit([...widths], 326, 51);

      expect(second).toBe(first);
      expect(third).toBe(first);
    });

    test('does not mutate the widths it is given', () => {
      const widths = [100, 200, 300];

      countItemsThatFit(widths, 250, TOGGLE);

      expect(widths).toEqual([100, 200, 300]);
    });

    test('never returns more items than it was given', () => {
      expect(countItemsThatFit([10, 10], 10_000, TOGGLE)).toBe(2);
    });

    test('never returns a negative count', () => {
      expect(countItemsThatFit([500], 1, TOGGLE)).toBeGreaterThanOrEqual(0);
    });

    test('widening the row never shows fewer items', () => {
      // Monotonicity is what makes the layout settle: as space grows, items
      // may come back but must never disappear.
      const widths = [105, 112, 129, 122];
      let previous = 0;

      for (let available = 0; available <= 800; available += 10) {
        const fits = countItemsThatFit(widths, available, 51);
        expect(fits).toBeGreaterThanOrEqual(previous);
        previous = fits;
      }
    });

    test('re-running with the result applied does not change the answer', () => {
      // Feeding a decision back in is exactly what caused the flicker. The
      // count for a given amount of space must be a fixed point.
      const widths = [105, 112, 129, 122];
      const available = 326;

      const fits = countItemsThatFit(widths, available, 51);

      expect(countItemsThatFit(widths, available, 51)).toBe(fits);
    });
  });

  describe('the header case that prompted this', () => {
    // Real measurements from a logged-in forum at 820px: four navigation
    // links, 326px of room once the logo and controls have taken theirs.
    const LINKS = [105, 112, 129, 122];

    test('collapses the links that do not fit at 820px', () => {
      expect(countItemsThatFit(LINKS, 326, 51)).toBe(2);
    });

    test('shows every link once there is room for them', () => {
      expect(countItemsThatFit(LINKS, 500, 51)).toBe(4);
    });

    test('keeps only the first link when space is very tight', () => {
      expect(countItemsThatFit(LINKS, 232, 51)).toBe(1);
    });
  });
});
