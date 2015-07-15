/**
 * The `punctuate` helper formats a list of strings (e.g. names) to read
 * fluently in the application's locale.
 *
 * @example
 * punctuate(['Toby', 'Franz', 'Dominion'])
 * // Toby, Franz, and Dominion
 *
 * @param {Array} items
 * @return {Array}
 */
export default function punctuate(items) {
  const punctuated = [];

  // FIXME: update to use translation
  items.forEach((item, i) => {
    punctuated.push(item);

    // If this item is not the last one, then we will follow it with some
    // punctuation. If the list is more than 2 items long, we'll add a comma.
    // And if this is the second-to-last item, we'll add 'and'.
    if (i < items.length - 1) {
      punctuated.push((items.length > 2 ? ', ' : '') + (i === items.length - 2 ? ' and ' : ''));
    }
  });

  return punctuated;
};
