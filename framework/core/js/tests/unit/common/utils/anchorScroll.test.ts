import { jest } from '@jest/globals';
import anchorScroll from '../../../../src/common/utils/anchorScroll';

afterEach(() => {
  document.body.innerHTML = '';
  jest.restoreAllMocks();
});

describe('anchorScroll', () => {
  // The post stream anchors to a post when a page of posts arrives, which can
  // be after the reader has left the discussion and the post is gone.
  it('redraws without anchoring when the element is not on the page', () => {
    const redraw = jest.fn();

    expect(() => anchorScroll('.PostStream-item[data-index="7"]', redraw)).not.toThrow();
    expect(redraw).toHaveBeenCalledTimes(1);
  });

  it('does not fail when the redraw removes the element', () => {
    document.body.innerHTML = '<div class="anchor"></div>';

    const redraw = jest.fn(() => {
      document.body.innerHTML = '';
    });

    expect(() => anchorScroll('.anchor', redraw)).not.toThrow();
    expect(redraw).toHaveBeenCalledTimes(1);
  });

  it('keeps the element where it was on screen across the redraw', () => {
    document.body.innerHTML = '<div class="anchor"></div>';

    // jsdom has no layout: the element sits at 500 before the redraw and 800
    // after it, with the window scrolled to 200.
    const tops = [500, 800];
    jest.spyOn(($ as any).fn, 'offset').mockImplementation(() => ({ top: tops.shift(), left: 0 }));

    const scrolledTo: number[] = [];
    jest.spyOn(($ as any).fn, 'scrollTop').mockImplementation(function (this: any, value?: number) {
      if (value === undefined) return 200;

      scrolledTo.push(value);

      return this;
    });

    const redraw = jest.fn();

    anchorScroll('.anchor', redraw);

    expect(redraw).toHaveBeenCalledTimes(1);
    // 300px below the top of the window before, so 300px below it after.
    expect(scrolledTo).toEqual([500]);
  });
});
