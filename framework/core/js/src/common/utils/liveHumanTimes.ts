import humanTime from './humanTime';

function updateHumanTimes() {
  document.querySelectorAll('[data-humantime]').forEach(function (el) {
    const ago = humanTime(el.getAttribute('datetime'));

    el.textContent = ago;
  });
}

/**
 * The `liveHumanTimes` initializer sets up a loop every 10 seconds to update
 * timestamps rendered with the `humanTime` helper.
 */
export default function liveHumanTimes() {
  setInterval(updateHumanTimes, 10000);
}
