import humanTime from './humanTime';

function updateHumanTimes() {
  $('[data-humantime]').each(function () {
    const $this = $(this);
    const ago = humanTime($this.attr('datetime'));

    $this.html(ago);
  });
}

/**
 * The `liveHumanTimes` initializer sets up a loop every 1 second to update
 * timestamps rendered with the `humanTime` helper.
 */
export default function liveHumanTimes() {
  setInterval(updateHumanTimes, 10000);
}
