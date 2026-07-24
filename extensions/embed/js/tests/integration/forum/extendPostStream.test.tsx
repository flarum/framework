import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import { hideFirstPost, scrollParentToReply } from '../../../src/forum/extendPostStream';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';

const coreJsDir = resolve(dirname(fileURLToPath(import.meta.url)), '../../../../../../framework/core/js');

beforeAll(() => {
  const cwd = process.cwd();

  try {
    process.chdir(coreJsDir);
    bootstrapForum();
  } finally {
    process.chdir(cwd);
  }
});

describe('hideFirstPost', () => {
  it('removes the first post when it is post number 1', () => {
    const vdom: any = { children: [{ attrs: { 'data-number': 1 } }, { attrs: { 'data-number': 2 } }] };
    hideFirstPost(vdom);
    expect(vdom.children).toHaveLength(1);
    expect(vdom.children[0].attrs['data-number']).toBe(2);
  });

  it('leaves the stream alone when the first child is not post 1', () => {
    const vdom: any = { children: [{ attrs: { 'data-number': 5 } }, { attrs: { 'data-number': 6 } }] };
    hideFirstPost(vdom);
    expect(vdom.children).toHaveLength(2);
  });

  it('does not throw on an empty stream', () => {
    const vdom: any = { children: [] };
    expect(() => hideFirstPost(vdom)).not.toThrow();
  });
});

describe('scrollParentToReply', () => {
  beforeEach(() => {
    app.boot();
    (app as any).composer = { isFullScreen: () => true };
  });

  afterEach(() => {
    delete (window as any).parentIFrame;
  });

  it('scrolls the parent frame to the reply when in an iframe and composer is full screen', () => {
    const scrollToOffset = jest.fn();
    (window as any).parentIFrame = { scrollToOffset };

    scrollParentToReply(123);

    expect(scrollToOffset).toHaveBeenCalledWith(0, 123);
  });

  it('does nothing when not embedded in a parent frame', () => {
    // No window.parentIFrame present.
    expect(() => scrollParentToReply(123)).not.toThrow();
  });

  it('does nothing when the composer is not full screen', () => {
    const scrollToOffset = jest.fn();
    (window as any).parentIFrame = { scrollToOffset };
    (app as any).composer = { isFullScreen: () => false };

    scrollParentToReply(123);

    expect(scrollToOffset).not.toHaveBeenCalled();
  });
});
