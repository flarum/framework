import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';
import Application from 'flarum/common/Application';
import extendPostStream, { hideFirstPost, scrollParentToReply } from '../../../src/forum/extendPostStream';
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

describe('the hideFirstPost option', () => {
  /**
   * The post numbers PostStream renders when the embed is opened at `url`,
   * going through the embed's start-up as the forum does.
   */
  function renderedNumbers(url: string, afterLoad: () => void = () => {}): number[] {
    // Stands in for PostStream: a stream that starts at post 1.
    class PostStream {
      view() {
        return { children: [{ attrs: { 'data-number': 1 } }, { attrs: { 'data-number': 2 } }] };
      }
    }

    window.history.replaceState(null, '', url);

    // Flarum's routes resolve a tick after the app mounts, so the route has
    // no params yet at that point.
    const route = m.route;
    (m as any).route = { param: () => undefined };
    const mount = jest.spyOn(Application.prototype as any, 'mount').mockImplementation(() => {});

    try {
      extendPostStream();
      (Application.prototype as any).mount.call(app);
    } finally {
      mount.mockRestore();
      (m as any).route = route;
    }

    afterLoad();

    // PostStream is code-split: its chunk registers it later.
    flarum.reg.add('core', 'forum/components/PostStream', PostStream);

    return new PostStream().view().children.map((child) => child.attrs['data-number']);
  }

  it('hides post 1 when the embed is opened with it', () => {
    expect(renderedNumbers('/embed/1?hideFirstPost=1')).toEqual([2]);
  });

  it('keeps hiding it once scrolling has rewritten the address without it', () => {
    expect(renderedNumbers('/embed/1?hideFirstPost=1', () => window.history.replaceState(null, '', '/embed/1/2'))).toEqual([2]);
  });

  it('shows post 1 otherwise', () => {
    expect(renderedNumbers('/embed/1')).toEqual([1, 2]);
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
