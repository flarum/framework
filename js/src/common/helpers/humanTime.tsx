import dayjs from 'dayjs';
import * as Mithril from 'mithril';
import humanTimeUtil from '../utils/humanTime';

/**
 * The `humanTime` helper displays a time in a human-friendly time-ago format
 * (e.g. '12 days ago'), wrapped in a <time> tag with other information about
 * the time.
 */
export default function humanTime(time: Date): Mithril.Vnode {
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
