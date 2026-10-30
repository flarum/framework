import app from '../app';

const DIRECTION_THRESHOLD = 10;
const REFRESH_THRESHOLD = 150;

type Gesture = {
  id: number;
  startX: number;
  startY: number;
  phase: 'tracking' | 'pulling';
};

export default function pullToRefresh() {
  if (document.querySelector('.PullToRefresh')) return;

  const host = document.createElement('div');
  host.className = 'PullToRefresh';
  host.dataset.state = 'idle';
  host.style.setProperty('--pull-threshold', String(REFRESH_THRESHOLD));
  document.body.appendChild(host);
  m.render(
    host,
    <div className="PullToRefresh-visual" aria-hidden="true">
      <svg className="PullToRefresh-ring" viewBox="0 0 24 24" focusable="false">
        <circle className="PullToRefresh-ring-bg" cx="12" cy="12" r="9" pathLength="100" />
        <circle className="PullToRefresh-ring-line" cx="12" cy="12" r="9" pathLength="100" />
      </svg>
    </div>
  );

  let gesture: Gesture | null = null;
  let refreshing = false;
  let frame: number | null = null;
  let distance = 0;

  document.addEventListener('touchstart', onTouchStart, { passive: true });
  document.addEventListener('touchmove', onTouchMove, { passive: false });
  document.addEventListener('touchend', onTouchEnd, { passive: true });
  document.addEventListener('touchcancel', reset, { passive: true });

  function cancelFrame() {
    if (frame !== null) cancelAnimationFrame(frame);
    frame = null;
  }

  function reset() {
    gesture = null;
    if (refreshing) return;

    cancelFrame();
    host.dataset.state = 'idle';
    host.style.setProperty('--pull-distance', '0');
  }

  function show(nextDistance: number) {
    distance = nextDistance;
    if (frame !== null) return;

    frame = requestAnimationFrame(() => {
      frame = null;
      host.dataset.state = 'pulling';
      host.style.setProperty('--pull-distance', String(distance));
    });
  }

  function isBusy(): boolean {
    return Boolean(app.composer?.isVisible() || app.modal?.isModalOpen());
  }

  function isInsideScroller(target: Element): boolean {
    for (let element: Element | null = target; element && element !== document.body; element = element.parentElement) {
      const { overflowY } = getComputedStyle(element);

      if (/^(auto|scroll)$/.test(overflowY) && element.scrollHeight > element.clientHeight) {
        return true;
      }
    }

    return false;
  }

  function onTouchStart(event: TouchEvent) {
    if (refreshing) return;
    reset();

    if (event.defaultPrevented || event.touches.length !== 1 || window.scrollY > 0 || isBusy()) {
      return;
    }

    const target = event.target;

    if (
      !(target instanceof Element) ||
      !target.closest('#content') ||
      target.closest('input, textarea, select, [contenteditable], [draggable="true"]') ||
      isInsideScroller(target)
    ) {
      return;
    }

    const touch = event.touches[0];

    gesture = {
      id: touch.identifier,
      startX: touch.clientX,
      startY: touch.clientY,
      phase: 'tracking',
    };
  }

  function onTouchMove(event: TouchEvent) {
    if (!gesture) return;

    if (event.defaultPrevented || event.touches.length !== 1 || window.scrollY > 0 || isBusy()) {
      reset();
      return;
    }

    const touch = event.touches[0];

    if (touch.identifier !== gesture.id) {
      reset();
      return;
    }

    const deltaX = touch.clientX - gesture.startX;
    const deltaY = touch.clientY - gesture.startY;

    if (gesture.phase === 'tracking') {
      if (Math.max(Math.abs(deltaX), Math.abs(deltaY)) < DIRECTION_THRESHOLD) {
        return;
      }

      if (deltaY <= -DIRECTION_THRESHOLD || Math.abs(deltaX) > Math.max(Math.abs(deltaY), DIRECTION_THRESHOLD) * 1.5) {
        reset();
        return;
      }

      if (deltaY < DIRECTION_THRESHOLD || Math.abs(deltaX) > deltaY) {
        return;
      }

      gesture.phase = 'pulling';
    }

    if (deltaY <= 0 || !event.cancelable) {
      reset();
      return;
    }

    event.preventDefault();

    show(deltaY);
  }

  function onTouchEnd(event: TouchEvent) {
    if (!gesture) return;

    const currentGesture = gesture;
    gesture = null;

    const touch = Array.from(event.changedTouches).find((touch) => touch.identifier === currentGesture.id);

    if (!touch || event.defaultPrevented || event.touches.length !== 0 || currentGesture.phase !== 'pulling' || window.scrollY > 0 || isBusy()) {
      reset();
      return;
    }

    const distance = touch.clientY - currentGesture.startY;

    if (distance >= REFRESH_THRESHOLD) {
      refreshing = true;
      cancelFrame();
      host.style.setProperty('--pull-distance', String(REFRESH_THRESHOLD));
      host.dataset.state = 'refreshing';
      window.setTimeout(() => window.location.reload(), 200);
    } else {
      reset();
    }
  }
}
