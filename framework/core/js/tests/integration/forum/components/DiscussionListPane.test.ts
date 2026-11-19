import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { jest } from '@jest/globals';
import app from '../../../../src/forum/app';
import DiscussionListPane from '../../../../src/forum/components/DiscussionListPane';
import mq from 'mithril-query';

beforeAll(() => {
  bootstrapForum();
  app.boot();
});

describe('DiscussionListPane', () => {
  let originalPane: typeof app.pane;
  let pane: any;

  beforeEach(() => {
    originalPane = app.pane;
    pane = { show: jest.fn(), onmouseleave: jest.fn() };
    // @ts-ignore
    app.pane = pane;
    // Leave the list itself out: these tests are about the pane around it.
    jest.spyOn(DiscussionListPane.prototype as any, 'enoughSpace').mockReturnValue(false);
  });

  afterEach(() => {
    // @ts-ignore
    app.pane = originalPane;
    jest.restoreAllMocks();
  });

  test('binds its handlers once the list arrives after it was first drawn empty', () => {
    // A discussion opened directly draws the pane before its list has loaded.
    let hasItems = false;
    const pageEl = mq(DiscussionListPane as any, { state: { hasItems: () => hasItems } });
    expect(pageEl).not.toHaveElement('.DiscussionListPane');

    hasItems = true;
    pageEl.redraw();
    const aside = pageEl.rootEl.querySelector('.DiscussionListPane') ?? pageEl.rootEl;

    $(aside).trigger('mouseenter');
    expect(pane.show).toHaveBeenCalledTimes(1);

    $(aside).trigger('mouseleave');
    expect(pane.onmouseleave).toHaveBeenCalledTimes(1);

    // Later redraws must not bind them again.
    pageEl.redraw();
    $(aside).trigger('mouseenter');
    expect(pane.show).toHaveBeenCalledTimes(2);
  });
});
