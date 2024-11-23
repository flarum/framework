/**
 * The `slidable` utility adds touch gestures to an element so that it can be
 * slid away to reveal controls underneath, and then released to activate those
 * controls.
 *
 * It relies on the element having children with particular CSS classes.
 *
 * The function returns a record with a `reset` property. This is a function
 * which reverts the slider to its original position. This should be called,
 * for example, when a controls dropdown is closed.
 *
 * @param {HTMLElement | SVGElement | Element} element
 * @return {{ reset : () => void }}
 */
export default function slidable(element) {
  const threshold = 50;

  let underneathLeft;
  let underneathRight;

  let startX;
  let startY;
  let couldBeSliding = false;
  let isSliding = false;
  let pos = 0;

  /**
   * Animate the slider to a new position.
   *
   * @param {number} newPos
   * @param {Partial<KeyframeAnimationOptions & { complete: () => void }>} [options]
   */
  const animatePos = (newPos, options = {}) => {
    options.duration ||= 200;

    const content = element.querySelector('.Slidable-content');
    const anim = content.animate(
      {
        transform: 'translate(' + newPos + 'px, 0)',
      },
      options
    );

    anim.addEventListener('finish', () => {
      content.style.transform = 'translate(' + newPos + 'px, 0)';
      anim.cancel();
      if (options.complete) options.complete();
    });
  };

  /**
   * Revert the slider to its original position.
   */
  const reset = () => {
    animatePos(0, {
      complete: function () {
        element.classList.remove('sliding');
        if (underneathLeft) underneathLeft.style.display = 'none';
        if (underneathRight) underneathRight.style.display = 'none';
        isSliding = false;
      },
    });
  };

  const content = element.querySelector('.Slidable-content');
  content.addEventListener('touchstart', (e) => {
    // Update the references to the elements underneath the slider, provided
    // they're not disabled.
    underneathLeft = element.querySelector('.Slidable-underneath--left:not(.disabled)');
    underneathRight = element.querySelector('.Slidable-underneath--right:not(.disabled)');

    startX = e.targetTouches[0].clientX;
    startY = e.targetTouches[0].clientY;

    couldBeSliding = true;
    pos = 0;
  });
  content.addEventListener('touchmove', function (e) {
    const newX = e.targetTouches[0].clientX;
    const newY = e.targetTouches[0].clientY;

    // Once the user moves their touch in a direction that's more up/down than
    // left/right, we'll assume they're scrolling the page. But if they do
    // move in a horizontal direction at first, then we'll lock their touch
    // into the slider.
    if (couldBeSliding && Math.abs(newX - startX) > Math.abs(newY - startY)) {
      isSliding = true;
    }
    couldBeSliding = false;

    if (isSliding) {
      pos = newX - startX;

      // If there are controls underneath the either side, then we'll show/hide
      // them depending on the slider's position. We also make the controls
      // icon get a bit bigger the further they slide.
      const toggle = (underneath, side) => {
        if (underneath) {
          const active = side === 'left' ? pos > 0 : pos < 0;

          if (active && underneath.classList.contains('Slidable-underneath--elastic')) {
            pos -= pos * 0.5;
          }
          underneath.style.display = active ? 'block' : 'none';

          const scale = Math.max(0, Math.min(1, (Math.abs(pos) - 25) / threshold));
          underneath.querySelector('.icon').style.transform = 'scale(' + scale + ')';
        } else {
          pos = Math[side === 'left' ? 'min' : 'max'](0, pos);
        }
      };

      toggle(underneathLeft, 'left');
      toggle(underneathRight, 'right');

      this.style.transform = 'translate(' + pos + 'px, 0)';

      element.classList.toggle('sliding', !!pos);

      e.preventDefault();
    }
  });
  content.addEventListener('touchend', (e) => {
    // If the user releases the touch and the slider is past the threshold
    // position on either side, then we will activate the control for that
    // side. We will also animate the slider's position all the way to the
    // other side, or back to its original position, depending on whether or
    // not the side is 'elastic'.
    const activate = (underneath) => {
      underneath.click();

      if (underneath.classList.contains('Slidable-underneath--elastic')) {
        reset();
      } else {
        animatePos((pos > 0 ? 1 : -1) * element.clientWidth);
      }
    };

    if (underneathRight && pos < -threshold) {
      activate(underneathRight);
    } else if (underneathLeft && pos > threshold) {
      activate(underneathLeft);
    } else {
      reset();
    }

    couldBeSliding = false;
    isSliding = false;
  });

  return { reset };
}
