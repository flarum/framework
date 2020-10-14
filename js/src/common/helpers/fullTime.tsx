import dayjs from 'dayjs';
import * as Mithrill from 'mithril';

/**
 * The `fullTime` helper displays a formatted time string wrapped in a <time>
 * tag.
 */
export default function fullTime(time: Date): Mithrill.Vnode {
  const d = dayjs(time);

  const datetime = d.format();
  const full = d.format('LLLL');

  return (
    <time pubdate datetime={datetime}>
      {full}
    </time>
  );
}
