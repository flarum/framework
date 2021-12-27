import Model from '../Model';
/**
 * The `computed` utility creates a function that will cache its output until
 * any of the dependent values are dirty.
 *
 * @param dependentKeys The keys of the dependent values.
 * @param compute The function which computes the value using the
 *     dependent values.
 */
export default function computed<T, M = Model>(...args: [...string[], (this: M, ...args: unknown[]) => T]): () => T;
