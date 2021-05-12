/**
 * The `extract` utility deletes a property from an object and returns its
 * value.
 *
 * @param object The object that owns the property
 * @param property The name of the property to extract
 * @return The value of the property
 */
export default function extract<T, K extends keyof T>(object: T, property: K): T[K];
