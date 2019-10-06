/**
 * The `extract` utility deletes a property from an object and returns its
 * value.
 */
export default function extract(object: object, property: string): any {
    const value = object[property];

    delete object[property];

    return value;
}
