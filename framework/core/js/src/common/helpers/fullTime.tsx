import dayjs from 'dayjs';
import type Mithril from 'mithril';
import app from '../app';

/**
 * The `fullTime` helper displays a formatted time string wrapped in a <time>
 * tag.
 */
export default function fullTime(time: Date): Mithril.Vnode {
  const d = dayjs(time);

  const datetime = d.format();
  const full = app.translator.formatDateTime(d, 'core.lib.datetime_formats.fullTime');

  return (
    <time pubdate datetime={datetime}>
      {full}
    </time>
  );
}
