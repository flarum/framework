/**
 * The `fullTime` helper displays a formatted time string wrapped in a <time>
 * tag.
 *
 * @param {Date} time
 * @return {Object}
 */
export default function fullTime(time) {
  const d = dayjs(time);

  const datetime = d.format();
  const full = d.format('LLLL');

  return (
    <time pubdate datetime={datetime}>
      {full}
    </time>
  );
}
