export default function humanTime(time) {
  var m = moment(time);

  var minute = 6e4;
  var hour = 36e5;
  var day = 864e5;
  var ago = null;

  var diff = m.diff(moment());

  if (diff < -30 * day) {
    if (m.year() === moment().year()) {
      ago = m.format('D MMM');
    } else {
      ago = m.format('MMM \'YY');
    }
  } else {
    ago = m.fromNow();
  }

  return ago;
};
