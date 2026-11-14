import type Mithril from 'mithril';
/**
 * The `punctuateSeries` helper formats a list of strings (e.g. names) to read
 * fluently in the application's locale.
 *
 * ```js
 * punctuateSeries(['Toby', 'Franz', 'Dominion']) // Toby, Franz, and Dominion
 * ```
 */
export default function punctuateSeries(items: Mithril.Children[]): Mithril.Children;
