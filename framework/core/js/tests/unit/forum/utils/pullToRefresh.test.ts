import { jest } from '@jest/globals';
import app from '../../../../src/forum/app';
import pullToRefresh from '../../../../src/forum/utils/pullToRefresh';

let reload: jest.Mock;
let content: HTMLElement;

function touch(type: string, target: Element, y: number, x = 100) {
  const point = { identifier: 1, clientX: x, clientY: y, target };
  const event = new Event(type, { bubbles: true, cancelable: true });
  const ending = type === 'touchend' || type === 'touchcancel';

  Object.defineProperty(event, 'touches', { value: ending ? [] : [point] });
  Object.defineProperty(event, 'changedTouches', { value: [point] });
  target.dispatchEvent(event);

  return event;
}

function pull(target: Element, from: number, to: number) {
  touch('touchstart', target, from);

  for (let y = from + 10; y <= to; y += 10) touch('touchmove', target, y);

  touch('touchend', target, to);
  jest.runOnlyPendingTimers();
}

beforeAll(() => {
  jest.useFakeTimers();
  content = document.createElement('div');
  content.id = 'content';
  document.body.appendChild(content);
});

afterAll(() => {
  jest.useRealTimers();
});

// Once a pull reloads the page, that instance stays refreshing until the
// reload, so each test starts its own, and takes its listeners away after.
let listeners: [EventTarget, string, EventListenerOrEventListenerObject][] = [];

beforeEach(() => {
  for (const target of [document, window]) {
    const add = target.addEventListener.bind(target);

    jest.spyOn(target, 'addEventListener').mockImplementation((type: string, listener: any, options?: any) => {
      listeners.push([target, type, listener]);
      add(type, listener, options);
    });
  }

  reload = jest.fn();
  pullToRefresh(reload);
});

afterEach(() => {
  jest.restoreAllMocks();
  listeners.forEach(([target, type, listener]) => target.removeEventListener(type, listener));
  listeners = [];
  document.querySelector('.PullToRefresh')?.remove();
});

describe('pull-to-refresh', () => {
  test('it is only added once', () => {
    pullToRefresh(reload);

    expect(document.querySelectorAll('.PullToRefresh')).toHaveLength(1);
  });

  test('pulling the page down far enough reloads it', () => {
    pull(content, 100, 300);

    expect(reload).toHaveBeenCalledTimes(1);
  });

  test('a short pull does not', () => {
    pull(content, 100, 200);

    expect(reload).not.toHaveBeenCalled();
    expect(document.querySelector<HTMLElement>('.PullToRefresh')!.dataset.state).toBe('idle');
  });

  test('pulling the page stops the browser scrolling it', () => {
    touch('touchstart', content, 100);
    touch('touchmove', content, 110);
    const move = touch('touchmove', content, 140);
    touch('touchcancel', content, 140);

    expect(move.defaultPrevented).toBe(true);
  });

  test('swiping up or sideways does not', () => {
    pull(content, 300, 100);

    touch('touchstart', content, 100, 100);
    touch('touchmove', content, 110, 200);
    touch('touchmove', content, 300, 200);
    touch('touchend', content, 300, 200);
    jest.runOnlyPendingTimers();

    expect(reload).not.toHaveBeenCalled();
  });

  test('pulling in a text field does not', () => {
    const input = document.createElement('textarea');
    content.appendChild(input);

    pull(input, 100, 300);

    expect(reload).not.toHaveBeenCalled();
    input.remove();
  });

  test('pulling outside the page content does not', () => {
    const header = document.createElement('header');
    document.body.appendChild(header);

    pull(header, 100, 300);

    expect(reload).not.toHaveBeenCalled();
    header.remove();
  });

  test('pulling while the composer or a modal is open does not', () => {
    jest.spyOn(app.composer, 'isVisible').mockReturnValue(true);
    pull(content, 100, 300);

    jest.restoreAllMocks();
    jest.spyOn(app.modal, 'isModalOpen').mockReturnValue(true);
    pull(content, 100, 300);

    expect(reload).not.toHaveBeenCalled();
  });

  test('pulling when the page is scrolled down does not', () => {
    Object.defineProperty(window, 'scrollY', { value: 200, configurable: true });
    pull(content, 100, 300);
    Object.defineProperty(window, 'scrollY', { value: 0, configurable: true });

    expect(reload).not.toHaveBeenCalled();
  });

  test('pulling inside something that scrolls of its own does not', () => {
    const list = document.createElement('div');
    list.style.overflowY = 'auto';
    Object.defineProperty(list, 'scrollHeight', { value: 500 });
    Object.defineProperty(list, 'clientHeight', { value: 100 });
    const item = document.createElement('p');
    list.appendChild(item);
    content.appendChild(list);

    pull(item, 100, 300);

    expect(reload).not.toHaveBeenCalled();
    list.remove();
  });

  test('a pull the browser cancels is let go', () => {
    touch('touchstart', content, 100);
    touch('touchmove', content, 110);
    touch('touchmove', content, 200);
    touch('touchcancel', content, 200);
    touch('touchend', content, 300);
    jest.runOnlyPendingTimers();

    expect(reload).not.toHaveBeenCalled();
    expect(document.querySelector<HTMLElement>('.PullToRefresh')!.dataset.state).toBe('idle');
  });
});
