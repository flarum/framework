import type Application from './Application';

// Used to fix typings
const w = window as any;

/**
 * Proxy app. Common JS is run first, at which point `window.app` is not
 * set as this is done by the namespaced JS.
 *
 * When the corrent value is set, this code would retain the reference to
 * the original invalid value.
 *
 * By using a proxy, we can ensure that our `window.app` value is always
 * up-to-date with the latest reference.
 */
const appProxy = new Proxy(
  {},
  {
    get(_, properties) {
      return Reflect.get(w.app, properties, w.app);
    },
    set(_, properties, value) {
      return Reflect.set(w.app, properties, value, w.app);
    },
  }
);

/**
 * The instance of Application within the common namespace.
 */
export default appProxy as Application;
