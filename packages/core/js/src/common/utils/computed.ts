import Model from '../Model';

/**
 * The `computed` utility creates a function that will cache its output until
 * any of the dependent values are dirty.
 *
 * @param dependentKeys The keys of the dependent values.
 * @param compute The function which computes the value using the
 *     dependent values.
 */
export default function computed<T, M = Model>(...args: [...string[], (this: M, ...args: unknown[]) => T]): () => T {
  const keys = args.slice(0, -1) as string[];
  const compute = args.slice(-1)[0] as (this: M, ...args: unknown[]) => T;

  const dependentValues: Record<string, unknown> = {};
  let computedValue: T;

  return function (this: M) {
    let recompute = false;

    // Read all of the dependent values. If any of them have changed since last
    // time, then we'll want to recompute our output.
    keys.forEach((key) => {
      const attr = (this as Record<string, unknown | (() => unknown)>)[key];
      const value = typeof attr === 'function' ? attr.call(this) : attr;

      if (dependentValues[key] !== value) {
        recompute = true;
        dependentValues[key] = value;
      }
    });

    if (recompute) {
      computedValue = compute.apply(
        this,
        keys.map((key) => dependentValues[key])
      );
    }

    return computedValue;
  };
}
