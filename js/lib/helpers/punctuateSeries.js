/**
 * The `punctuateSeries` helper formats a list of strings (e.g. names) to read
 * fluently in the application's locale.
 *
 * ```js
 * punctuateSeries(['Toby', 'Franz', 'Dominion']) // Toby, Franz, and Dominion
 * ```
 *
 * @param {Array} items
 * @return {VirtualElement}
 */
export default function punctuateSeries(items) {
  if (items.length === 2) {
    return app.trans('core.lib.series_two_text', {
      first: items[0],
      second: items[1]
    });
  } else if (items.length >= 3) {
    // If there are three or more items, we will join all of the items up until
    // the second-to-last one with the equivalent of a comma, and then we will
    // feed that into the translator along with the last two items.
    const first = items
      .slice(0, items.length - 2)
      .reduce((list, item) => list.concat([item, app.trans('core.lib.series_glue_text')]), [])
      .slice(0, -1);

    return app.trans('core.lib.series_three_text', {
      first,
      second: items[items.length - 2],
      third: items[items.length - 1]
    });
  }

  return items;
}
