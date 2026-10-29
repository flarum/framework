import { jest } from '@jest/globals';
import slidable from '../../../../src/forum/utils/slidable';

/**
 * Helper — build a minimal DOM structure that slidable() expects:
 *
 *   <div class="DiscussionListItem Slidable">
 *     <span class="Slidable-underneath Slidable-underneath--left disabled"></span>
 *     <span class="Slidable-underneath Slidable-underneath--right disabled"></span>
 *     <div class="Slidable-content">
 *       <a class="DiscussionListItem-main" href="#">Discussion title</a>
 *     </div>
 *   </div>
 */
function makeSlider() {
  const el = document.createElement('div');
  el.className = 'DiscussionListItem Slidable';
  el.innerHTML = `
    <span class="Slidable-underneath Slidable-underneath--left disabled"></span>
    <span class="Slidable-underneath Slidable-underneath--right disabled"></span>
    <div class="Slidable-content">
      <a class="DiscussionListItem-main" href="#">Discussion title</a>
    </div>
  `;
  document.body.appendChild(el);
  return el;
}

/**
 * Fire a jQuery touch event on the element with the given touch coordinates.
 */
function triggerTouch(el: Element, type: string, clientX: number, clientY: number) {
  $(el).trigger(
    $.Event(type, {
      originalEvent: {
        targetTouches: [{ clientX, clientY }],
        preventDefault: () => {},
      },
    } as any)
  );
}

beforeEach(() => {
  // Stub jQuery animate to a no-op to avoid the infinite requestAnimationFrame
  // loop that jQuery's animation scheduler enters in the jsdom test environment.
  jest.spyOn($.fn, 'animate').mockImplementation(function (this: JQuery) {
    return this;
  });
});

afterEach(() => {
  document.body.innerHTML = '';
  jest.restoreAllMocks();
});

describe('slidable', () => {
  describe('plain tap (no movement)', () => {
    it('does not call animate on a plain tap — allows synthetic click to fire', () => {
      const el = makeSlider();
      const content = el.querySelector('.Slidable-content') as HTMLElement;

      slidable(el);

      const animateSpy = $.fn.animate as jest.MockedFunction<typeof $.fn.animate>;
      animateSpy.mockClear();

      // Simulate a tap: touchstart then touchend at the same position.
      triggerTouch(content, 'touchstart', 100, 200);
      triggerTouch(content, 'touchend', 100, 200);

      expect(animateSpy).not.toHaveBeenCalled();
    });
  });

  describe('partial slide (below threshold, slider moved)', () => {
    it('calls animate to reset when slider moved but did not reach threshold', () => {
      const el = makeSlider();

      // Enable the left control so the slider is actually allowed to move rightward.
      el.querySelector('.Slidable-underneath--left')!.classList.remove('disabled');

      const content = el.querySelector('.Slidable-content') as HTMLElement;

      slidable(el);

      const animateSpy = $.fn.animate as jest.MockedFunction<typeof $.fn.animate>;
      animateSpy.mockClear();

      // Slide 20px to the right — below the 50px threshold. With a left control
      // enabled, pos stays at 20 (not clamped to 0), so reset() should animate back.
      triggerTouch(content, 'touchstart', 100, 200);
      triggerTouch(content, 'touchmove', 120, 200);
      triggerTouch(content, 'touchend', 120, 200);

      expect(animateSpy).toHaveBeenCalled();
    });
  });

  describe('full slide past threshold', () => {
    it('activates the right control when swiped left past threshold', () => {
      const el = makeSlider();

      // Enable the right underneath element.
      const right = el.querySelector('.Slidable-underneath--right') as HTMLElement;
      right.classList.remove('disabled');
      const clickSpy = jest.fn();
      $(right).on('click', clickSpy);

      const content = el.querySelector('.Slidable-content') as HTMLElement;
      slidable(el);

      // Swipe 60px to the left — past the 50px threshold.
      triggerTouch(content, 'touchstart', 100, 200);
      triggerTouch(content, 'touchmove', 40, 200);
      triggerTouch(content, 'touchend', 40, 200);

      expect(clickSpy).toHaveBeenCalled();
    });

    it('activates the left control when swiped right past threshold', () => {
      const el = makeSlider();

      // Enable the left underneath element.
      const left = el.querySelector('.Slidable-underneath--left') as HTMLElement;
      left.classList.remove('disabled');
      const clickSpy = jest.fn();
      $(left).on('click', clickSpy);

      const content = el.querySelector('.Slidable-content') as HTMLElement;
      slidable(el);

      // Swipe 60px to the right — past the 50px threshold.
      triggerTouch(content, 'touchstart', 100, 200);
      triggerTouch(content, 'touchmove', 160, 200);
      triggerTouch(content, 'touchend', 160, 200);

      expect(clickSpy).toHaveBeenCalled();
    });
  });

  describe('vertical scroll (not a slide)', () => {
    it('does not call animate when touch moves vertically (page scroll)', () => {
      const el = makeSlider();
      const content = el.querySelector('.Slidable-content') as HTMLElement;

      slidable(el);

      const animateSpy = $.fn.animate as jest.MockedFunction<typeof $.fn.animate>;
      animateSpy.mockClear();

      // Move more vertically than horizontally — treated as page scroll, not a slide.
      triggerTouch(content, 'touchstart', 100, 200);
      triggerTouch(content, 'touchmove', 102, 250);
      triggerTouch(content, 'touchend', 102, 250);

      expect(animateSpy).not.toHaveBeenCalled();
    });
  });

  describe('reset()', () => {
    it('returns a reset function that animates the slider back to zero', () => {
      const el = makeSlider();

      const { reset } = slidable(el);

      const animateSpy = $.fn.animate as jest.MockedFunction<typeof $.fn.animate>;
      animateSpy.mockClear();

      reset();

      expect(animateSpy).toHaveBeenCalled();
    });
  });
});
