/**
 * The `computed` utility creates a function that will cache its output until
 * any of the dependent values are dirty.
 *
 * @param {...String} dependentKeys The keys of the dependent values.
 * @param {function} compute The function which computes the value using the
 *     dependent values.
 * @return {Function}
 */
export default function computed(...dependentKeys: string[]): Function;
