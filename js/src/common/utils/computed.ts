/**
 * The `computed` utility creates a function that will cache its output until
 * any of the dependent values are dirty.
 *
 * @param dependentKeys The keys of the dependent values.
 * @param compute The function which computes the value using the
 *     dependent values.
 */
export default function computed(dependentKeys: string | string[], compute: Function): () => any {
    const keys = [].concat(dependentKeys);

    const dependentValues = {};
    let computedValue;

    return function() {
        let recompute = false;

        // Read all of the dependent values. If any of them have changed since last
        // time, then we'll want to recompute our output.
        keys.forEach(key => {
            const value = typeof this[key] === 'function' ? this[key]() : this[key];

            if (dependentValues[key] !== value) {
                recompute = true;
                dependentValues[key] = value;
            }
        });

        if (recompute) {
            computedValue = compute.apply(
                this,
                keys.map(key => dependentValues[key])
            );
        }

        return computedValue;
    };
}
