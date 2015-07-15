/**
 * The `humanTime` utility converts a date to a localized, human-readable time-
 * ago string.
 *
 * @param {Date} time
 * @return {String}
 */
export default function humanTime(time) {
  const m = moment(time);

  const day = 864e5;
  const diff = m.diff(moment());
  let ago = null;

  // If this date was more than a month ago, we'll show the name of the month
  // in the string. If it wasn't this year, we'll show the year as well.
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
