/**
 * The `classList` utility creates a list of class names by joining an object's
 * keys, but only for values which are truthy.
 *
 * @example
 * classList({ foo: true, bar: false, qux: 'qaz' });
 * // "foo qux"
 *
 * @param {IClassParam} classes
 * @return {string}
 */
export default function classList(classes: IClassParam): string {
  let classNames: Array<string>;

  if (classes instanceof Array) {
    classNames = classes.filter((name) => name);
  } else {
    classNames = [];

    for (const i in classes) {
      if (classes[i]) classNames.push(i);
    }
  }

  return classNames.join(' ');
}

export interface IClassParam {
  [name: string]: boolean | string | number;
}
