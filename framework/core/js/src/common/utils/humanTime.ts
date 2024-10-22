import app from '../app';
import dayjs from 'dayjs';

/**
 * The `humanTime` utility converts a date to a localized, human-readable time-
 * ago string.
 */
export default function humanTime(time: dayjs.ConfigType): string {
  let d = dayjs(time);
  const now = dayjs();

  // To prevent showing things like "in a few seconds" due to small offsets
  // between client and server time, we always reset future dates to the
  // current time. This will result in "just now" being shown instead.
  if (d.isAfter(now)) {
    d = now;
  }

  let ago: string;

  // If this date was more than a month ago, we'll show the name of the month
  // in the string. If it wasn't this year, we'll show the year as well.
  if (d.diff(now, 'day') < -30) {
    if (d.isSame(now, 'year')) {
      ago = app.translator.formatDateTime(d, 'core.lib.datetime_formats.humanTimeShort');
    } else {
      ago = app.translator.formatDateTime(d, 'core.lib.datetime_formats.humanTimeFull');
    }
  } else {
    ago = d.fromNow();
  }

  return ago;
}
