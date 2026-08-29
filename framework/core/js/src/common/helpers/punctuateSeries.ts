import type { Children } from 'mithril';
import app from '../../common/app';

/**
 * Formats a list of strings/nodes to read fluently in the locale.
 */
export default function punctuateSeries(items: Children[] = []): Children {
  if (items.length === 2) {
    return app.translator.trans('core.lib.series.two_text', {
      first: items[0],
      second: items[1],
    });
  }

  if (items.length >= 3) {
    const glue = app.translator.trans('core.lib.series.glue_text') as Children;

    // Build middle items safely: [item, glue, item, glue, ...]
    const second: Children[] = [];
    for (let i = 1; i < items.length - 1; i++) {
      second.push(items[i]);
      if (i < items.length - 2) second.push(glue);
    }

    return app.translator.trans('core.lib.series.three_text', {
      first: items[0],
      second,
      third: items[items.length - 1],
    });
  }

  // 0 or 1 item: return as-is (Mithril can render arrays too)
  return items;
}
