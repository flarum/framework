import type Mithril from 'mithril';
import app from '../../common/app';

/**
 * The `punctuateSeries` helper formats a list of strings (e.g. names) to read
 * fluently in the application's locale.
 *
 * ```js
 * punctuateSeries(['Toby', 'Franz', 'Dominion']) // Toby, Franz, and Dominion
 * ```
 */
export default function punctuateSeries(items: Mithril.Children[]): Mithril.Children {
  if (items.length === 2) {
    return app.translator.trans('core.lib.series.two_text', {
      first: items[0],
      second: items[1],
    });
  }

  if (items.length >= 3) {
    // If there are three or more items, we will join all but the first and
    // last items with the equivalent of a comma, and then we will feed that
    // into the translator along with the first and last item.
    const second: Mithril.Children[] = [];

    for (let i = 1; i < items.length - 1; i++) {
      // Translate the glue each time: a vnode must not appear twice in a tree.
      if (i > 1) second.push(app.translator.trans('core.lib.series.glue_text'));
      second.push(items[i]);
    }

    return app.translator.trans('core.lib.series.three_text', {
      first: items[0],
      second,
      third: items[items.length - 1],
    });
  }

  return items;
}
