import { clampSplit, clampWidth, widthToShow } from '../../../../src/forum/utils/deckSizes';

describe('widthToShow', () => {
  // Three columns of 280 in a 1200px row: each shows 400. Making one show 500
  // needs a width that, stretched with the others, comes out at 500.
  it('accounts for columns stretching to fill a row with room to spare', () => {
    const width = widthToShow(500, 560, 1200);
    const total = width + 560;

    expect(Math.round((width * 1200) / total)).toBe(500);
  });

  it('is the size itself once the row is full and scrolls', () => {
    expect(widthToShow(500, 900, 1200)).toBe(500);
  });

  it('stays within the limits the server keeps', () => {
    expect(widthToShow(100, 900, 1200)).toBe(240);
    expect(widthToShow(2000, 900, 1200)).toBe(900);
  });

  it('leaves a column alone in its row at the minimum, as it fills the row anyway', () => {
    expect(widthToShow(600, 0, 1200)).toBe(240);
  });
});

describe('limits', () => {
  it('keeps widths to whole pixels within bounds', () => {
    expect([clampWidth(10), clampWidth(333.6), clampWidth(5000)]).toEqual([240, 334, 900]);
  });

  it('keeps the split between a fifth and four fifths', () => {
    expect([clampSplit(0), clampSplit(0.6543), clampSplit(1)]).toEqual([0.2, 0.654, 0.8]);
  });
});
