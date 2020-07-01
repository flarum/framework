import humanTimeUtil from '../utils/humanTime';

/**
 * The `humanTime` helper displays a time in a human-friendly time-ago format
 * (e.g. '12 days ago'), wrapped in a <time> tag with other information about
 * the time.
 *
 * @param {Date} time
 * @return {Object}
 */
export default function humanTime(time) {
  const d = dayjs(time);

  const datetime = d.format();
  const full = d.format('LLLL');
  const ago = humanTimeUtil(time);

  return (
    <time pubdate datetime={datetime} title={full} data-humantime>
      {ago}
    </time>
  );
}
